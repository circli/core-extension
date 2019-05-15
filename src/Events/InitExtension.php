<?php declare(strict_types=1);

namespace Circli\Core\Events;

use Circli\Contracts\InitCliApplication;

class InitExtension
{
    public function __construct(InitCliApplication $extension)
    {
    }
}