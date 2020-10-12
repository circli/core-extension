<?php declare(strict_types=1);

namespace Circli\Core;

use DI\Definition\StringDefinition;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class Config implements ContainerInterface
{
    private array $config = [];
    private string $configPath;
    /** @var string[] */
    private array $files = [];
    /** @var string[] */
    private array $loaded = [];

    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;
    }

    public function loadFile(string $file): void
    {
        $this->files[] = $file;
    }

    public function add(array $config)
    {
        $this->config = array_merge($this->config, $config);
    }

    public function getLoadedFiles(): array
    {
        return $this->loaded;
    }

    /**
     * @inheritdoc
     */
    public function get($id)
    {
        if ($this->has($id)) {
            $value = $this->config[$id];
            if ($value instanceof StringDefinition) {
                return $value->resolve($this);
            }
            return $value;
        }
        throw new class ('Config value not found: ' . $id) extends \RuntimeException implements NotFoundExceptionInterface {};
    }

    /**
     * @inheritdoc
     */
    public function has($id)
    {
        if (!\array_key_exists($id, $this->config) && count($this->files)) {
            $configs = [];
            foreach ($this->files as $configFile) {
                if (!file_exists($this->configPath . $configFile)) {
                    throw new \RuntimeException('Configuration not found: ' . $this->configPath . $configFile);
                }
                $configs[] = require $this->configPath . $configFile;
                $this->loaded[] = $configFile;
            }
            $this->files = [];
            $this->config = array_merge($this->config, ...$configs);
        }
        return \array_key_exists($id, $this->config);
    }
}
