<?php declare(strict_types=1);

namespace Circli\Core\Events;

use Circli\Contracts\InitCliApplication;
use Symfony\Component\Console\Command\Command;

final class InitCliCommands
{
    /** @var Command[] */
    private $commands;
    /** @var InitCliApplication */
    private $application;

    public function __construct(InitCliApplication $application)
    {
        $this->application = $application;
    }

    public function getApplication(): InitCliApplication
    {
        return $this->application;
    }

    public function getCommands(): array
    {
        return $this->commands;
    }
}
