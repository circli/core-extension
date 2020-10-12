<?php declare(strict_types=1);

namespace Circli\Core\Conditions;

use Circli\Core\Extensions;

final class ExtensionLoaded implements ConditionInterface
{
    private string $extensionName;

    public function __construct(string $extensionName)
    {
        $this->extensionName = $extensionName;
    }

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
