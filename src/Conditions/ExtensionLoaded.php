<?php declare(strict_types=1);

namespace Circli\Core\Conditions;

use Circli\Core\Extensions;

final class ExtensionLoaded implements ConditionInterface
{
    public function __construct(
        private string $extensionName,
    ) {}

    public function evaluate(...$args): bool
    {
        foreach ($args as $arg) {
            if ($arg instanceof Extensions) {
                return $arg->isLoaded($this->extensionName);
            }
        }

        return false;
    }
}
