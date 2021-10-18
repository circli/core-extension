<?php declare(strict_types=1);

namespace Circli\Core;

use Circli\Contracts\EventProviderInterface;
use Circli\Contracts\EventSubscriberInterface;
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
use Circli\EventDispatcher\ListenerProvider\ContainerListenerProvider;
use Circli\EventDispatcher\ListenerProvider\DefaultProvider;
use Circli\EventDispatcher\ListenerProvider\PriorityAggregateProvider;
use DI\ContainerBuilder as DiContainerBuilder;
use Fig\EventDispatcher\AggregateProvider;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use function DI\autowire;
use function class_exists;
use function DI\create;
use function file_exists;
use function is_string;

abstract class ContainerBuilder
{
    protected EventDispatcherInterface $eventDispatcher;
    protected ContainerInterface $container;
    protected PriorityAggregateProvider $eventListenerProvider;
    protected bool $allowDiCompile = true;
    protected bool $forceCompile = false;
    protected Context $context;
    protected Extensions $extensionRegistry;
    /** @var ConditionalDefinition[] */
    private array $deferredDefinitions = [];
    private PathContainer $pathContainer;

    public function __construct(
        protected Environment $environment,
        protected string $basePath,
        ?PathContainer $pathContainer = null,
    ) {
        $this->pathContainer = $pathContainer ?? new DefaultPathContainer($this->basePath);
        $this->eventListenerProvider = new PriorityAggregateProvider();
        $this->eventDispatcher = new EventDispatcher($this->eventListenerProvider);
        $this->context = php_sapi_name() === 'cli' ? Context::CONSOLE() : Context::SERVER();
    }

    protected function initDefinitions(DiContainerBuilder $builder, string $defaultDefinitionPath)
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
        $containerBuilder = new DiContainerBuilder();
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

        $containerBuilder = $this->preBuild($containerBuilder);

        $configPath = $this->basePath . '/config/';
        $config = new Config($configPath);
        $config->add([
            'app.context' => $this->context,
            'app.mode' => $this->environment,
            'app.basePath' => $this->basePath,
        ]);

        $configPath = $this->pathContainer->getConfigPath();
        $definitionPath = $configPath . 'container/';

        $configFile = $this->environment . '.php';
        //support for local dev config
        if ($this->environment->is(Environment::DEVELOPMENT()) && file_exists($configPath . 'local.php')) {
            $configFile = 'local.php';
        }
        $config->loadFile($configFile);

        $this->extensionRegistry = new Extensions();
        $containerBuilder->addDefinitions([
            AggregateProvider::class => create(AggregateProvider::class),
            Config::class => $config,
            Context::class => $this->context,
            DefaultProvider::class => new DefaultProvider(),
            Environment::class => $this->environment,
            EventDispatcherInterface::class => $this->eventDispatcher,
            Extensions::class => $this->extensionRegistry,
            PathContainer::class => $this->pathContainer,
            PriorityAggregateProvider::class => $this->eventListenerProvider
        ]);
        $containerBuilder->addDefinitions($definitionPath . 'core.php');
        $containerBuilder->addDefinitions($definitionPath . 'logger.php');

        $extensions = $this->pathContainer->loadConfigFile('extensions');
        $this->setupExtensions($extensions, $containerBuilder, $this->pathContainer);

        $modules = $this->pathContainer->loadConfigFile('modules');
        $this->setupExtensions($modules, $containerBuilder, $this->pathContainer);

        foreach ($this->deferredDefinitions as $def) {
            if ($def->getCondition()->evaluate($this->extensionRegistry, $this->environment, $this->context)) {
                $containerBuilder->addDefinitions($def->getDefinitions());
            }
        }

        // Run site specific definitions last so that they override definitions from modules and extensions
        $this->initDefinitions($containerBuilder, $definitionPath);

        $this->container = $containerBuilder->build();

        $coreEventProvider = new CoreEventProvider($this->container);
        $this->postProcessExtensions($coreEventProvider);

        if ($this->forceCompile) {
            return $this->container;
        }

        $this->eventListenerProvider->addProvider($coreEventProvider);
        $this->eventListenerProvider->addProvider($this->container->get(DefaultProvider::class));
        $this->eventListenerProvider->addProvider($this->container->get(AggregateProvider::class));
        $this->eventDispatcher->dispatch(new PostContainerBuild($this));

        return $this->container;
    }

    protected function preBuild(DiContainerBuilder $containerBuilder): DiContainerBuilder
    {
        return $containerBuilder;
    }

    private function postProcessExtensions(CoreEventProvider $eventProvider): void
    {
        $cliApplications = $this->extensionRegistry->filterAllByInterface(InitCliApplication::class);
        /** @var InitCliApplication $application */
        foreach ($cliApplications as $application) {
            $this->eventDispatcher->dispatch(new InitCliCommands($application, $this->container));
        }

        $hasEventProviders = $this->extensionRegistry->filterAllByInterface(EventProviderInterface::class);
        /** @var EventProviderInterface $ext */
        foreach ($hasEventProviders as $ext) {
            $this->eventListenerProvider->addProvider($ext->getEventProvider($this->container));
        }

        $hasEventSubscribes = $this->extensionRegistry->filterAllByInterface(EventSubscriberInterface::class);
        /** @var EventSubscriberInterface $ext */
        foreach ($hasEventSubscribes as $ext) {
            foreach ($ext->getSubscribedEvents() as $event => $callback) {
                $eventProvider->subscribe($event, $callback);
            }
        }
    }

    private function setupExtensions(
        array $extensions,
        DiContainerBuilder $containerBuilder,
        PathContainer $pathContainer
    ): void {
        foreach ($extensions as $extensionName => $extension) {
            if (!(is_string($extension) && class_exists($extension))) {
                continue;
            }
            $extension = new $extension($pathContainer);
            if ($extension instanceof ModuleInterface || is_int($extensionName)) {
                $this->extensionRegistry->addModule(get_class($extension), $extension);
                if ($extension instanceof ModuleInterface) {
                    $initEvent = new InitModule($extension);
                }
            }
            else {
                $this->extensionRegistry->addExtension($extensionName, $extension);
                $initEvent = new InitExtension($extension);
            }
            if ($extension instanceof ExtensionInterface) {
                $defs = $extension->configure();
                if (isset($defs[0])) {
                    foreach ($defs as $def) {
                        if ($def instanceof ConditionalDefinition) {
                            $this->deferredDefinitions[] = $def;
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
            $this->eventDispatcher->dispatch($initEvent);
        }
    }
}
