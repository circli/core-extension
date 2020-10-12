<?php declare(strict_types=1);

namespace Circli\Core\Events;

use Circli\Contracts\ModuleInterface;

class InitModule
{
    private ModuleInterface $module;

    public function __construct(ModuleInterface $module)
    {
        $this->module = $module;
    }

    public function getModule(): ModuleInterface
    {
        return $this->module;
    }
}
