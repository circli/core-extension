<?php declare(strict_types=1);

namespace Circli\Core;

use Circli\Console\Application;
use Circli\Console\ContainerCommandResolver;
use Circli\Core\Events\InitCliCommands;
use Circli\EventDispatcher\ListenerProvider\DefaultProvider;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class Cli
{
    protected ContainerInterface $container;
    protected Container $containerBuilder;
    protected EventDispatcherInterface $eventDispatcher;
    protected DefaultProvider $eventListenerProvider;

    /** @var Command[] */
    protected array $commands;
    protected Application $application;

    public function __construct(Environment $mode, string $basePath = null)
    {
        if (class_exists(\Cli\Container::class)) {
            $containerClass = \Cli\Container::class;
        }
        elseif (class_exists(\App\Container::class)) {
            $containerClass = \App\Container::class;
        }
        else {
            throw new \RuntimeException('No container builder found');
        }

        $containerBuilder = new $containerClass($mode, $basePath ?? \dirname(__DIR__, 3));
        if (!$containerBuilder instanceof Container) {
            throw new \RuntimeException('Container must extend: ' . Container::class);
        }
        $this->containerBuilder = $containerBuilder;
        $this->eventDispatcher = $this->containerBuilder->getEventDispatcher();
        $this->eventListenerProvider = new DefaultProvider();
        $this->containerBuilder->getEventListenerProvider()->addProvider($this->eventListenerProvider);
        $this->eventListenerProvider->listen(InitCliCommands::class, function (InitCliCommands $event) {
            $event->getApplication()->initCli($this->application, $event->getContainer());
        });
        $this->container = $this->containerBuilder->build();
        $this->application = new Application(new ContainerCommandResolver($this->container));
    }

    public function run(): int
    {
        return $this->application->run();
    }

    public function runCommand(string $command, $args = null): int
    {
        if (class_exists($command) && method_exists($command, 'getDefaultName')) {
            $command = $command::getDefaultName();
        }

        if (!$command) {
            throw new \InvalidArgumentException('Could\'t find command');
        }

        $commandInstance = $this->application->find($command);
        if (is_array($args)) {
            if (isset($args[0])) {
                array_unshift($args, $commandInstance->getName());
                $input = new StringInput(implode(' ', $args));
            }
            else {
                $args['command'] = $commandInstance->getName();
                $input = new ArrayInput($args);
            }
        }
        else {
            $input = new StringInput($commandInstance->getName() . (is_string($args) ? ' ' . $args : ''));
        }

        return $commandInstance->run($input, new ConsoleOutput());
    }
}
