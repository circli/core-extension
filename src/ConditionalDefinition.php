<?php declare(strict_types=1);

namespace Circli\Core;

use Circli\Core\Conditions\ClassExists;
use Circli\Core\Conditions\ConditionInterface;
use Circli\Core\Conditions\ContextCondition;
use Circli\Core\Conditions\EnvironmentCondition;
use Circli\Core\Conditions\ExtensionLoaded;
use Circli\Core\Conditions\ModuleLoaded;
use Circli\Core\Enum\Context;

class ConditionalDefinition
{
    private string|array $def;
    private ConditionInterface $condition;

    public static function classExist(string $class, $def): self
    {
        return new self($def, new ClassExists($class));
    }

    public static function moduleLoaded(string $module, $def): self
    {
        return new self($def, new ModuleLoaded($module));
    }

    public static function extensionLoaded(string $ext, $def): self
    {
        return new self($def, new ExtensionLoaded($ext));
    }

    public static function environment(Environment $env, $def): self
    {
        return new self($def, new EnvironmentCondition($env));
    }

    public static function context(Context $context, $def): self
    {
        return new self($def, new ContextCondition($context));
    }

    public function __construct($def, ConditionInterface $condition)
    {
        $this->def = $def;
        $this->condition = $condition;
    }

    public function getDefinitions(): array
    {
        if (is_string($this->def) && file_exists($this->def)) {
            return include $this->def;
        }
        return $this->def;
    }

    public function getCondition(): ConditionInterface
    {
        return $this->condition;
    }
}
