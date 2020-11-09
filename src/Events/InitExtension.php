<?php declare(strict_types=1);

namespace Circli\Core\Events;

use Circli\Core\Extension;

final class InitExtension
{
    private $extension;

    public function __construct($extension)
    {
        $this->extension = $extension;
    }

    public function getExtension()
    {
        return $this->extension;
    }
}
