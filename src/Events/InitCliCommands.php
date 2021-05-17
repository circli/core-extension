<?php declare(strict_types=1);

namespace Circli\Core\Events;

use Circli\Contracts\InitCliApplication;
use Psr\Container\ContainerInterface;

final class InitCliCommands
{
    public function __construct(
        private InitCliApplication $application,
        private ContainerInterface $container
    ) {}

    public function getApplication(): InitCliApplication
    {
        return $this->application;
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }
}
