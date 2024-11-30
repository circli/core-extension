<?php declare(strict_types=1);

namespace Circli\Core\Command\Definition;

use Circli\Console\Definition;
use Circli\Core\Command\ContainerCompilerHandler;
use Circli\Core\Command\Input\ContainerCompilerInput;
use Circli\Core\Environment;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ContainerCompiler extends Definition
{
    protected function configure(): void
    {
        $this
            ->setName('circli:di:compile')
            ->addOption(
                'environment',
                'e',
                InputOption::VALUE_OPTIONAL,
                'Environment to compile container for',
                'production'
            )
            ->addArgument('container', InputArgument::OPTIONAL, 'Container class to compile. Need fqcn')
            ->setDescription('Compile di:container')
            ->setCommand(ContainerCompilerHandler::class);
    }

    public function transformInput(InputInterface $input, OutputInterface $output): InputInterface
    {
        $env = Environment::fromValue($input->getOption('environment'));
        $containerClass = $input->getArgument('container') ?? \App\Container::class;
        return new ContainerCompilerInput($env, $containerClass);
    }
}
