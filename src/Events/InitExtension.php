<?php declare(strict_types=1);

namespace Circli\Core\Events;

final class InitExtension
{
    private InitExtension $extension;

    public function __construct(InitExtension $extension)
    {
        $this->extension = $extension;
    }

    public function getExtension(): InitExtension
    {
        return $this->extension;
    }
}
