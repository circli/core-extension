<?php declare(strict_types=1);

namespace Circli\Core\Command;

use Circli\Console\Definition;
use Circli\Core\Container;
use Circli\Core\Environment;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ContainerCompiler extends Definition
{
    protected static $defaultName = 'circli:di:compile';
    /** @var string */
    private $basePath;

    public function __construct(string $basePath)
    {
        parent::__construct();
        $this->basePath = $basePath;
    }

    protected function configure(): void
    {
        parent::configure();
        $this
            ->addOption(
                'environment',
                'e',
                InputOption::VALUE_OPTIONAL,
                'Environment to compile container for',
                'production'
            )
            ->addArgument('container', InputArgument::OPTIONAL, 'Container class to compile. Need fqcn')
            ->setDescription('Compile di:container');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $env = Environment::fromValue($input->getOption('environment'));
        $containerClass = $input->getArgument('container') ?? \App\Container::class;

        if (!class_exists($containerClass)) {
            throw new \InvalidArgumentException('Can\'t find container class: ' . $containerClass);
        }

        $containerBuilder = new $containerClass($env, $this->basePath);
        if (!$containerBuilder instanceof Container) {
            throw new \RuntimeException('Invalid container type. Container must extend ' . Container::class);
        }
        $output->writeln('Found container: ' . get_class($containerBuilder));
        $containerBuilder->forceCompile();
        $path = $containerBuilder->getCompilePath();

        $output->write("Cleaning output directory\t\t\t\t");
        $this->cleanCompileDirectory($path);
        $output->writeln('<info>Ok</info>');

        $output->write('Compiling container to: ' . str_replace($this->basePath, '', $path) . "\t\t\t");
        $containerBuilder->build();
        $output->writeln('<info>Ok</info>');
    }

    private function cleanCompileDirectory(string $compileDir): void
    {
        if (!is_dir($compileDir)) {
            return;
        }

        $files = glob($compileDir . '/*');
        foreach ($files as $file) {
            is_dir($file) ? $this->cleanCompileDirectory($file) : unlink($file);
        }
        rmdir($compileDir);
    }
}
