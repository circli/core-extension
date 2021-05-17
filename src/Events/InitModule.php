<?php declare(strict_types=1);

namespace Circli\Core\Events;

use Circli\Contracts\ModuleInterface;

class InitModule
{
    public function __construct(
        private ModuleInterface $module
    ) {}

    public function getModule(): ModuleInterface
    {
        return $this->module;
    }
}
