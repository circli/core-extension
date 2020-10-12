<?php declare(strict_types=1);

namespace Circli\Core\Events;

use Circli\Core\Container;

class PostContainerBuild
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function getContainer(): Container
    {
        return $this->container;
    }
}
