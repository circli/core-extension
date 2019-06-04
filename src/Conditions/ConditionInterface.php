<?php declare(strict_types=1);

namespace Circli\Core\Conditions;

interface ConditionInterface
{
    public function evaluate(...$args): bool;
}