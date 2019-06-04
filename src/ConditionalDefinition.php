<?php declare(strict_types=1);

namespace Circli\Core;

use Circli\Core\Conditions\ConditionInterface;

class ConditionalDefinition
{
    private $def;
    /** @var ConditionInterface */
    private $condition;

    public function __construct($def, ConditionInterface $condition)
    {
        $this->def = $def;
        $this->condition = $condition;
    }

    public function getDefinitions(): array
    {
        if (file_exists($this->def)) {
            return include $this->def;
        }
        return $this->defs;
    }

    public function getCondition(): ConditionInterface
    {
        return $this->condition;
    }
}
