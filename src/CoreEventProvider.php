<?php declare(strict_types=1);

namespace Circli\Core;

use Circli\EventDispatcher\ListenerProvider\DefaultProvider;
use Psr\Container\ContainerInterface;

final class CoreEventProvider extends DefaultProvider
{
    public function __construct(
        private ContainerInterface $container,
    ) {}

    /**
     * @param class-string|string $event
     * @param class-string $service
     */
    public function addService(string $event, string $service): void
    {
        $this->listen($event, function (object $event) use ($service) {
            $instance = $this->container->get($service);
            $instance($event);
        });
    }

    /**
     * @param class-string|string $event
     * @param class-string|callable $callable
     */
    public function subscribe(string $event, $callable): void
    {
        if (is_callable($callable)) {
            $this->listen($event, $callable);
        }
        else {
            $this->addService($event, $callable);
        }
    }
}
