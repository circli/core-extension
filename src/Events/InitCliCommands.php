<?php declare(strict_types=1);

namespace Circli\Core\Events;

use Circli\Contracts\InitCliApplication;
use Psr\Container\ContainerInterface;

final class InitCliCommands
{
    /** @var InitCliApplication */
    private $application;
    /** @var ContainerInterface */
    private $container;

    public function __construct(InitCliApplication $application, ContainerInterface $container)
    {
        $this->application = $application;
        $this->container = $container;
    }

    public function getApplication(): InitCliApplication
    {
        return $this->application;
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }
}
