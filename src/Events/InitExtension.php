<?php declare(strict_types=1);

namespace Circli\Core\Events;

use Circli\Core\Extension;

final class InitExtension
{
    public function __construct(
        private object $extension,
    ) {}

    public function getExtension(): object
    {
        return $this->extension;
    }
}
