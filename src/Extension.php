<?php declare(strict_types=1);

namespace Circli\Core;

use Circli\Console\Application;
use Circli\Contracts\InitCliApplication;
use Circli\Core\Command\CrontabCompile;
use Circli\Core\Command\Definition\ContainerCompiler;
use Psr\Container\ContainerInterface;

class Extension implements InitCliApplication
{
    public function initCli(Application $cli, ContainerInterface $container)
    {
        $cli->addDefinition(new ContainerCompiler());
        $cli->addDefinition($container->get(CrontabCompile::class));
    }
}
