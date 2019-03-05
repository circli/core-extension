<?php declare(strict_types=1);

namespace Circli\Core;

use Circli\EventDispatcher\ListenerResolverInterface;
use Psr\Container\ContainerInterface;

class EventListenerResolver implements ListenerResolverInterface
{
    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function resolve($listener): callable
    {
        if (\is_callable($listener)) {
            return $listener;
        }

        $handler = null;
        if (\is_array($listener)) {
            [$listener, $handler] = $listener;
        }

        if (class_exists($listener)) {
            $cls = $this->container->get($listener);
            if ($handler && method_exists($cls, $handler)) {
                return [$cls, $handler];
            }

            if (\is_callable($cls)) {
                return $cls;
            }
        }

        return function () {};
    }
}
