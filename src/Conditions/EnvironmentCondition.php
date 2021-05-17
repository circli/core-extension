<?php declare(strict_types=1);

namespace Circli\Core\Conditions;

use Circli\Core\Environment;

final class EnvironmentCondition implements ConditionInterface
{
    public function __construct(
        private Environment $environment,
    ) {}

    public function evaluate(...$args): bool
    {
        foreach ($args as $arg) {
            if ($arg instanceof Environment) {
                return $arg->is($this->environment);
            }
        }
        return false;
    }
}
