<?php declare(strict_types=1);

namespace Circli\Core;

use Circli\Contracts\ExtensionInterface;
use Circli\Contracts\InitCliApplication;
use Circli\Contracts\ModuleInterface;
use Circli\Contracts\PathContainer;
use Circli\Core\Enum\Context;
use Circli\Core\Events\InitCliCommands;
use Circli\Core\Events\InitExtension;
use Circli\Core\Events\InitModule;
use Circli\Core\Events\PostContainerBuild;
use Circli\EventDispatcher\EventDispatcher;
use Circli\EventDispatcher\ListenerProvider\DefaultProvider;
use Circli\EventDispatcher\ListenerProvider\PriorityAggregateProvider;
use DI\ContainerBuilder;
use Fig\EventDispatcher\AggregateProvider;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use function class_exists;
use function count;
use function DI\autowire;
use function file_exists;
use function is_array;
use function is_string;

abstract class Container
{
    protected Environment $environment;
    protected string $basePath;
    protected EventDispatcherInterface $eventDispatcher;
    protected ContainerInterface $container;
    protected PriorityAggregateProvider $eventListenerProvider;
    protected bool $allowDiCompile = true;
    protected bool $forceCompile = false;
    protected Context $context;

    protected Extensions $extensionRegistry;

    public function __construct(Environment $environment, string $basePath)
    {
        $this->environment = $environment;
        $this->basePath = $basePath;
        $this->eventListenerProvider = new PriorityAggregateProvider();
        $this->eventDispatcher = new EventDispatcher($this->eventListenerProvider);
        $this->context = php_sapi_name() === 'cli' ? Context::CONSOLE() : Context::SERVER();
    }

    abstract protected function getPathContainer(): PathContainer;

    protected function initDefinitions(ContainerBuilder $builder, string $defaultDefinitionPath)
    {
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    public function getEventListenerProvider(): AggregateProvider
    {
        return $this->eventListenerProvider;
    }

    public function getCompilePath(): string
    {
        return $this->basePath . '/resources/di';
    }

    public function forceCompile(): void
    {
        $this->forceCompile = true;
    }

    public function build(): ContainerInterface
    {
        $containerBuilder = new ContainerBuilder();
        if ($this->forceCompile || (
                $this->allowDiCompile && (
                    $this->environment->is(Environment::PRODUCTION()) ||
                    $this->environment->is(Environment::STAGING())
                )
            )
        ) {
            if (is_writable($this->getCompilePath())) {
                $containerBuilder->enableCompilation($this->getCompilePath());
                $containerBuilder->writeProxiesToFile(true, $this->getCompilePath() . '/cache/di/proxies');
            }
        }

        $pathContainer = $this->getPathContainer();
        $configPath = $this->basePath . '/config/';
        $config = new Config($configPath);
        $config->add([
            'app.context' => $this->context,
            'app.mode' => $this->environment,
            'app.basePath' => $this->basePath,
        ]);


        $configPath = $pathContainer->getConfigPath();
        $definitionPath = $configPath . 'container/';

        $configFile = $this->environment . '.php';
        //support for local dev config
        if ($this->environment->is(Environment::DEVELOPMENT()) && file_exists($configPath . 'local.php')) {
            $configFile = 'local.php';
        }
        $config->loadFile($configFile);

        $this->extensionRegistry = new Extensions();
        $containerBuilder->addDefinitions([
            AggregateProvider::class => $this->eventListenerProvider,
            Config::class => $config,
            Context::class => $this->context,
            DefaultProvider::class => autowire(DefaultProvider::class),
            Environment::class => $this->environment,
            EventDispatcherInterface::class => $this->eventDispatcher,
            Extensions::class => $this->extensionRegistry,
            PathContainer::class => $pathContainer,
            PriorityAggregateProvider::class => $this->eventListenerProvider
        ]);
        $containerBuilder->addDefinitions($definitionPath . 'core.php');
        $containerBuilder->addDefinitions($definitionPath . 'logger.php');

        $deferredDefinitions = [];
        $cliApplications = [];
        $extensions = $pathContainer->loadConfigFile('extensions');
        foreach ($extensions as $extensionName => $extension) {
            if (!(is_string($extension) && class_exists($extension))) {
                continue;
            }
            $extension = new $extension($pathContainer);
            $this->extensionRegistry->addExtension($extensionName, $extension);
            if ($extension instanceof ExtensionInterface) {
                $defs = $extension->configure();
                if (isset($defs[0])) {
                    foreach ($defs as $def) {
                        if ($def instanceof ConditionalDefinition) {
                            $deferredDefinitions[] = $def;
                        }
                        else {
                            $containerBuilder->addDefinitions($def);
                        }
                    }
                }
                else {
                    $containerBuilder->addDefinitions($defs);
                }
            }
            if ($extension instanceof ListenerProviderInterface) {
                $this->eventListenerProvider->addProvider($extension);
            }
            if ($extension instanceof InitCliApplication) {
                $cliApplications[] = $extension;
            }
            $this->eventDispatcher->dispatch(new InitExtension($extension));
        }

        $modules = $pathContainer->loadConfigFile('modules');
        if (is_array($modules) && count($modules)) {
            foreach ($modules as $module) {
                if (!(is_string($module) && class_exists($module))) {
                    continue;
                }

                $module = new $module($pathContainer);
                $this->extensionRegistry->addModule(get_class($module), $module);
                if ($module instanceof ModuleInterface) {
                    $definitions = $module->configure();
                    if (isset($definitions[0])) {
                        foreach ($definitions as $def) {
                            if ($def instanceof ConditionalDefinition) {
                                $deferredDefinitions[] = $def;
                            }
                            else {
                                $containerBuilder->addDefinitions($def);
                            }
                        }
                    }
                    else {
                        $containerBuilder->addDefinitions($definitions);
                    }
                }
                if ($module instanceof ListenerProviderInterface) {
                    $this->eventListenerProvider->addProvider($module);
                }
                if ($module instanceof InitCliApplication) {
                    $cliApplications[] = $module;
                }
                $this->eventDispatcher->dispatch(new InitModule($module));
            }
        }

        if ($deferredDefinitions) {
            foreach ($deferredDefinitions as $def) {
                if ($def->getCondition()->evaluate($this->extensionRegistry, $this->environment, $this->context)) {
                    $containerBuilder->addDefinitions($def->getDefinitions());
                }
            }
        }

        // Run site specific definitions last so that they override definitions from modules and extensions
        $this->initDefinitions($containerBuilder, $definitionPath);

        $this->container = $containerBuilder->build();
        foreach ($cliApplications as $application) {
            $this->eventDispatcher->dispatch(new InitCliCommands($application, $this->container));
        }

        if ($this->forceCompile) {
            return $this->container;
        }
        $this->eventListenerProvider->addProvider($this->container->get(DefaultProvider::class));
        $this->eventDispatcher->dispatch(new PostContainerBuild($this));

        return $this->container;
    }

    public function getModules(): array
    {
        return $this->extensionRegistry->getModules();
    }

    public function getExtensions(): array
    {
        return $this->extensionRegistry->getExtensions();
    }
}
