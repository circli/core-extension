<?php declare(strict_types=1);

namespace Circli\Core\Conditions;

class ClassExists implements ConditionInterface
{
    /** @var string */
    private $className;

    public function __construct(string $className)
    {
        $this->className = $className;
    }

    public function evaluate(...$args): bool
    {
        return class_exists($this->className);
    }
}