<?php declare(strict_types=1);

namespace Circli\Core\Conditions;

use Circli\Core\Enum\Context;

final class ContextCondition implements ConditionInterface
{
    private Context $context;

    public static function server(): self
    {
        return new self(Context::SERVER());
    }

    public static function console(): self
    {
        return new self(Context::CONSOLE());
    }

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function evaluate(...$args): bool
    {
        foreach ($args as $arg) {
            if ($arg instanceof Context) {
                return $arg->is($this->context);
            }
        }
        return false;
    }
}
