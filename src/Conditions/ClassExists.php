<?php declare(strict_types=1);

namespace Circli\Core\Conditions;

class ClassExists implements ConditionInterface
{
    public function __construct(
        private string $className,
    ) {}

    public function evaluate(...$args): bool
    {
        return class_exists($this->className);
    }
}
