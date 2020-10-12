<?php declare(strict_types=1);

namespace Circli\Core;

use Circli\Contracts\ModuleInterface;

final class Extensions
{
    /** @var array<string, int> */
    private $names = [];
    /** @var array<string, object> */
    private $extensions = [];
    /** @var array<string, object> */
    private $modules = [];

    public function addExtension(string $name, $extension): void
    {
        $this->extensions[$name] = $extension;
        $this->names[get_class($extension)] = 1;
        $this->names[$name] = 1;
    }

    public function addModule(string $name, $module): void
    {
        $this->modules[$name] = $module;
        $this->names[$name] = 1;
    }

    public function isLoaded(string $name): bool
    {
        return array_key_exists($name, $this->names);
    }

    public function isExtension(string $name): bool
    {
        return array_key_exists($name, $this->extensions);
    }

    public function isModule(string $name): bool
    {
        return array_key_exists($name, $this->modules);
    }

    public function getModule(string $name): ?ModuleInterface
    {
        return $this->modules[$name] ?? null;
    }

    public function getModules()
    {
        return array_values($this->modules);
    }

    public function getExtensions(): array
    {
        return array_values($this->extensions);
    }
}
