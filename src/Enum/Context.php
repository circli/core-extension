<?php declare(strict_types=1);

namespace Circli\Core\Enum;

use Sunkan\Enum\Enum;

/**
 * @method static Context CONSOLE()
 * @method static Context SERVER()
 */
final class Context extends Enum
{
    private const CONSOLE = 'console';
    private const SERVER = 'server';
}
