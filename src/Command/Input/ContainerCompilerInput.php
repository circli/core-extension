<?php declare(strict_types=1);

namespace Circli\Core\Command\Input;

use Circli\Console\AbstractInput;
use Circli\Core\Environment;

final class ContainerCompilerInput extends AbstractInput
{
    public function __construct(
        private Environment $environment,
        private string $containerClass,
    ) {}

    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    public function getContainerClass(): string
    {
        return $this->containerClass;
    }
}
