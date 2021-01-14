<?php declare(strict_types=1);

namespace Circli\Core;

use Circli\Contracts\ExtensionInterface;
use Circli\Contracts\ModuleInterface;

final class Extensions
{
    /** @var array<string, int> */
    private array $names = [];
    /** @var array<string, object> */
    private array $extensions = [];
    /** @var array<string, ModuleInterface> */
    private array $modules = [];

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

    /**
     * @return ModuleInterface[]
     */
    public function getModules(): array
    {
        return array_values($this->modules);
    }

    /**
     * @return ExtensionInterface[]|object[]
     */
    public function getExtensions(): array
    {
        return array_values($this->extensions);
    }

    /**
     * @template T
     * @param class-string<T> $interface
     * @return array<T>
     */
    public function filterModulesByInterface(string $interface): array
    {
        $return = [];
        foreach ($this->modules as $module) {
            if ($module instanceof $interface) {
                $return[] = $module;
            }
        }
        return $return;
    }

    /**
     * @template T
     * @param class-string<T> $interface
     * @return array<T>
     */
    public function filterExtensionsByInterface(string $interface): array
    {
        $return = [];
        foreach ($this->modules as $module) {
            if ($module instanceof $interface) {
                $return[] = $module;
            }
        }
        return $return;
    }

    /**
     * @template T
     * @param class-string<T> $interface
     * @return array<T>
     */
    public function filterAllByInterface(string $interface): array
    {
        $return = [];
        foreach ($this->extensions as $extension) {
            if ($extension instanceof $interface) {
                $return[] = $extension;
            }
        }
        foreach ($this->modules as $module) {
            if ($module instanceof $interface) {
                $return[] = $module;
            }
        }
        return $return;
    }
}
