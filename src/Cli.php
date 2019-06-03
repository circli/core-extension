<?php declare(strict_types=1);

namespace Circli\Core;

use Circli\Core\Events\InitCliCommands;
use Circli\EventDispatcher\ListenerProvider\DefaultProvider;
use Circli\WebCore\Container;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

class Cli
{
    protected const NAME = 'CircliConsoleApplication';
    protected const VERSION = '{VERSION}';

    /** @var ContainerInterface */
    protected $container;
    /** @var Container */
    protected $containerBuilder;
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;
    /** @var DefaultProvider */
    protected $eventListenerProvider;
    /** @var Command[] */
    protected $commands;
    /** @var Application */
    protected $application;

    public function __construct(Environment $mode, string $basePath = null)
    {
        if (class_exists(\Cli\Container::class)) {
            $containerClass = \Cli\Container::class;
        }
        elseif (class_exists(\App\Container::class)) {
            $containerClass = \App\Container::class;
        }

        $this->containerBuilder = new $containerClass($mode, $basePath ?? \dirname(__DIR__, 3));
        $this->eventDispatcher = $this->containerBuilder->getEventDispatcher();
        $this->eventListenerProvider = new DefaultProvider();
        $this->containerBuilder->getEventListenerProvider()->addProvider($this->eventListenerProvider);
        $this->application = new Application(static::NAME, static::VERSION);
        $this->eventListenerProvider->listen(InitCliCommands::class, function (InitCliCommands $event) {
            $event->getApplication()->initCli($this->application, $event->getContainer());
        });
        $this->container = $this->containerBuilder->build();
    }

    public function run(): int
    {
        return $this->application->run();
    }
}
