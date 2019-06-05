<?php declare(strict_types=1);

namespace Circli\Core;

use Circli\Core\Events\InitCliCommands;
use Circli\Core\Events\PostContainerBuild;
use function class_exists;
use function count;
use function file_exists;
use function is_array;
use function is_string;
use Circli\Contracts\ExtensionInterface;
use Circli\Contracts\InitAdrApplication;
use Circli\Contracts\InitCliApplication;
use Circli\Contracts\ModuleInterface;
use Circli\Contracts\PathContainer;
use Circli\Core\Events\InitExtension;
use Circli\Core\Events\InitModule;
use Circli\EventDispatcher\EventDispatcher;
use DI\ContainerBuilder;
use Fig\EventDispatcher\AggregateProvider;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

abstract class Container
{
    /** @var Environment */
    protected $environment;
    /** @var string */
    protected $basePath;
    /** @var array */
    protected $modules = [];
    /** @var array */
    protected $extensions = [];
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;
    /** @var ContainerInterface */
    protected $container;
    /** @var AggregateProvider */
    protected $eventListenerProvider;

    public function __construct(Environment $environment, string $basePath)
    {
        $this->environment = $environment;
        $this->basePath = $basePath;
        $this->eventListenerProvider = new AggregateProvider();
        $this->eventDispatcher = new EventDispatcher($this->eventListenerProvider);
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

    public function build(): ContainerInterface
    {
        $containerBuilder = new ContainerBuilder();
        if ($this->environment->is(Environment::PRODUCTION()) ||
            $this->environment->is(Environment::STAGING())
        ) {
            $containerBuilder->enableCompilation($this->getCompilePath());
            $containerBuilder->writeProxiesToFile(true, $this->getCompilePath() . '/cache/di/proxies');
        }

        $pathContainer = $this->getPathContainer();
        $configPath = $this->basePath . '/config/';
        $config = new Config($configPath);
        $config->add([
            'app.mode' => $this->environment,
            'app.basePath' => $this->basePath,
        ]);

        $containerBuilder->addDefinitions([
            'app.mode' => $this->environment,
        ]);

        $configPath = $pathContainer->getConfigPath();
        $definitionPath = $configPath . 'container/';

        $configFile = $this->environment . '.php';
        //support for local dev config
        if ($this->environment->is(Environment::DEVELOPMENT()) && file_exists($configPath . 'local.php')) {
            $configFile = 'local.php';
        }
        $config->loadFile($configFile);

        $containerBuilder->addDefinitions([Config::class => $config]);
        $containerBuilder->addDefinitions([EventDispatcherInterface::class => $this->eventDispatcher]);
        $containerBuilder->addDefinitions($definitionPath . 'core.php');
        $containerBuilder->addDefinitions($definitionPath . 'logger.php');

        $extensionRegistry = new Extensions();
        $deferredDefinitions = [];

        $cliApplications = [];
        $extensions = $pathContainer->loadConfigFile('extensions');
        foreach ($extensions as $extensionName => $extension) {
            if (is_string($extension) && class_exists($extension)) {
                $extension = new $extension($pathContainer);
                $extensionRegistry->addExtension($extensionName, $extension);
            }
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

            $this->extensions[] = $extension;
        }

        $this->initDefinitions($containerBuilder, $definitionPath);

        $modules = $pathContainer->loadConfigFile('modules');
        if (is_array($modules) && count($modules)) {
            foreach ($modules as $module) {
                if (is_string($module) && class_exists($module)) {
                    $module = new $module($pathContainer);
                    $extensionRegistry->addModule(get_class($module), $module);
                }
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
                if ($module instanceof InitAdrApplication) {
                    $this->eventDispatcher->dispatch(new InitModule($module));
                }

                $this->modules[] = $module;
            }
        }

        if ($deferredDefinitions) {
            foreach ($deferredDefinitions as $def) {
                if ($def->getCondition()->evaluate($extensionRegistry)) {
                    $containerBuilder->addDefinitions($def->getDefinitions());
                }
            }
        }

        $this->container = $containerBuilder->build();
        foreach ($cliApplications as $application) {
            $this->eventDispatcher->dispatch(new InitCliCommands($application, $this->container));
        }

        $this->eventDispatcher->dispatch(new PostContainerBuild($this));

        $this->eventListenerProvider->addProvider($this->container->get(AggregateProvider::class));

        return $this->container;
    }
}
