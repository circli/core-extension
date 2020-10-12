<?php declare(strict_types=1);

namespace Circli\Core\Conditions;

class ClassExists implements ConditionInterface
{
    private string $className;

    public function __construct(string $className)
    {
        $this->className = $className;
    }

    public function evaluate(...$args): bool
    {
        return class_exists($this->className);
    }
}
