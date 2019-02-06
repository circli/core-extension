<?php

namespace Circli\Core;

use Circli\Contracts\EventProviderInterface;
use Circli\Contracts\EventSubscriberInterface;
use Circli\Contracts\ExtensionInterface;
use Circli\Contracts\InitAdrApplication;
use Circli\Contracts\InitCliApplication;
use Circli\Contracts\ModuleInterface;
use Circli\Core\Events\InitExtension;
use Circli\Core\Events\InitModule;
use Circli\EventDispatcher\EventDispatcher;
use Circli\EventDispatcher\EventDispatcherInterface;
use Circli\EventDispatcher\LazyListenerFactory;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;

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

    public function __construct(Environment $environment, string $basePath)
    {
        $this->environment = $environment;
        $this->basePath = $basePath;
        $this->eventDispatcher = new EventDispatcher();
    }

    abstract protected function getPathContainer(): \Circli\Contracts\PathContainer;

    protected function initDefinitions(ContainerBuilder $builder, string $defaultDefinitionPath)
    {
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
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
            $containerBuilder->enableCompilation($this->basePath . '/cache/di');
            $containerBuilder->writeProxiesToFile(true, $this->basePath . '/cache/di/proxies');
        }

        $pathContainer = $this->getPathContainer();

        $configPath = $this->basePath . '/config/';
        $config = new Config($configPath);
        $config->add([
            'app.mode' => $this->environment,
        ]);

        $containerBuilder->addDefinitions([
            'app.mode' => $this->environment,
        ]);

        $configPath = $pathContainer->getConfigPath();
        $definitionPath = $configPath . 'container/';

        $configFile = $this->environment . '.php';
        //support for local dev config
        if ($this->environment->is(Environment::DEVELOPMENT()) && \file_exists($configPath . 'local.php')) {
            $configFile = 'local.php';
        }
        $config->loadFile($configFile);

        $containerBuilder->addDefinitions([Config::class => $config]);
        $containerBuilder->addDefinitions([EventDispatcherInterface::class => $this->eventDispatcher]);
        $containerBuilder->addDefinitions($definitionPath . 'core.php');
        $containerBuilder->addDefinitions($definitionPath . 'logger.php');

        $eventProvider = [];
        $extensions = $pathContainer->loadConfigFile('extensions');
        foreach ($extensions as $extension) {
            if (\is_string($extension) && \class_exists($extension)) {
                $extension = new $extension($pathContainer);
            }
            if ($extension instanceof ExtensionInterface) {
                $containerBuilder->addDefinitions($extension->configure());
            }
            if ($extension instanceof EventProviderInterface) {
                $eventProvider[] = $extension;
            }
            if ($extension instanceof InitCliApplication) {
                $this->eventDispatcher->trigger(new InitExtension($extension));
            }

            $this->extensions[] = $extension;
        }

        $this->initDefinitions($containerBuilder, $definitionPath);

        $modules = $pathContainer->loadConfigFile('modules');
        if (\is_array($modules) && \count($modules)) {
            foreach ($modules as $module) {
                if (\is_string($module) && \class_exists($module)) {
                    $module = new $module($pathContainer);
                }
                if ($module instanceof ModuleInterface) {
                    $definitions = $module->configure();
                    $containerBuilder->addDefinitions($definitions);
                }
                if ($module instanceof EventProviderInterface) {
                    $eventProvider[] = $module;
                }
                if ($module instanceof InitCliApplication) {
                    $this->eventDispatcher->trigger(new InitExtension($module));
                }
                if ($module instanceof InitAdrApplication) {
                    $this->eventDispatcher->trigger(new InitModule($module));
                }

                $this->modules[] = $module;
            }
        }

        $this->container = $containerBuilder->build();

        if (\count($eventProvider)) {
            $lazyFactory = new LazyListenerFactory(new EventListenerResolver($this->container));
            foreach ($eventProvider as $providerCls) {
                $providerCls->registerEvents($this->eventDispatcher, $lazyFactory);
            }
        }

        return $this->container;
    }
}
