<?php declare(strict_types=1);

namespace Circli\Core;

use Circli\Contracts\ExtensionInterface;
use Circli\Contracts\InitCliApplication;
use Circli\Contracts\PathContainer;
use Circli\Core\Command\ContainerCompiler;
use Circli\Core\Command\CrontabCompile;
use Psr\Container\ContainerInterface;

class Extension implements ExtensionInterface, InitCliApplication
{
    public function __construct(PathContainer $paths)
    {
    }

    public function configure(PathContainer $pathContainer = null): array
    {
        return [
            ContainerCompiler::class => function (ContainerInterface $container) {
                $config = $container->get(Config::class);
                return new ContainerCompiler($config->get('app.basePath'));
            },
        ];
    }

    /**
     * @param \Symfony\Component\Console\Application|\Circli\Console\Application $cli
     */
    public function initCli($cli, ContainerInterface $container)
    {
        $cli->add($container->get(ContainerCompiler::class));
        $cli->add($container->get(CrontabCompile::class));
    }
}
