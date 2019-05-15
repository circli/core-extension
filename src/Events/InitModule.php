<?php declare(strict_types=1);

namespace Circli\Core\Events;

use Circli\Contracts\InitAdrApplication;

class InitModule
{
    /** @var InitAdrApplication */
    protected $module;

    public function __construct(InitAdrApplication $module)
    {
        $this->module = $module;
    }

    public function getModule(): InitAdrApplication
    {
        return $this->module;
    }
}
