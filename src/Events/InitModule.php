<?php declare(strict_types=1);

namespace Circli\Core\Events;

use Circli\Contracts\ModuleInterface;

class InitModule
{
    private $module;

    public function __construct($module)
    {
        $this->module = $module;
    }

    public function getModule()
    {
        return $this->module;
    }
}
