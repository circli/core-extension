<?php declare(strict_types=1);

namespace Circli\Core\Conditions;

final class AggregateCondition implements ConditionInterface
{
    /** @var ConditionInterface[] */
    private $conditions;

    public function __construct(ConditionInterface ...$conditions)
    {
        $this->conditions = $conditions;
    }

    public function add(ConditionInterface $condition): void
    {
        $this->conditions[] = $condition;
    }

    public function evaluate(...$args): bool
    {
        foreach ($this->conditions as $condition) {
            if (!$condition->evaluate(...$args)) {
                return false;
            }
        }
        return true;
    }
}
