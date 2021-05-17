<?php declare(strict_types=1);

namespace Circli\Core\Events;

use Circli\Core\ContainerBuilder;

class PostContainerBuild
{
    public function __construct(
        private ContainerBuilder $container,
    ) {}

    public function getContainer(): ContainerBuilder
    {
        return $this->container;
    }
}
