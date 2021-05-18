<?php declare(strict_types=1);

namespace Circli\Core\Command;

use Circli\Contracts\PathContainer;
use Circli\Core\Command\Input\ContainerCompilerInput;
use Circli\Core\ContainerBuilder;
use Symfony\Component\Console\Output\OutputInterface;

class ContainerCompilerHandler
{
    private string $basePath;

    public function __construct(PathContainer $pathContainer)
    {
        $this->basePath = $pathContainer->getBasePath();
    }

    public function __invoke(ContainerCompilerInput $input, OutputInterface $output): int
    {
        $containerClass = $input->getContainerClass();
        if (!class_exists($containerClass)) {
            throw new \InvalidArgumentException('Can\'t find container class: ' . $containerClass);
        }

        $containerBuilder = new $containerClass($input->getEnvironment(), $this->basePath);
        if (!$containerBuilder instanceof ContainerBuilder) {
            throw new \RuntimeException('Invalid container type. Container must extend ' . ContainerBuilder::class);
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
        return 0;
    }

    private function cleanCompileDirectory(string $compileDir): void
    {
        if (!is_dir($compileDir)) {
            return;
        }
        if ($compileDir === sys_get_temp_dir()) {
            return;
        }

        $files = glob($compileDir . '/*');
        foreach ($files as $file) {
            is_dir($file) ? $this->cleanCompileDirectory($file) : unlink($file);
        }
        rmdir($compileDir);
    }
}
