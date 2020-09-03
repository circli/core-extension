<?php declare(strict_types=1);

namespace Circli\Core;

use Actus\Path;

class PathResolver
{
    protected $ns = [
        'config:',
        'resources:',
        'template:',
    ];
    private $actus;

    public function __construct(Path $path)
    {
        $this->actus = $path;
    }

    public function get(string $path): ?string
    {
        $realPath = $this->actus->get($path);

        [$alias, $filePath] = explode(':', $path);
        if (!$realPath && strpos($filePath, '/')) {
            [$moduleAlias, $filePath] = explode('/', $filePath, 2);
            $modulePath = $moduleAlias . '-' . $alias . ':' . $filePath;
            $realPath = $this->actus->get($modulePath);
        }

        return $realPath;
    }

    public function add(string $module, string $ns, string $path)
    {
        $alias = $module . '-' . $ns;

        $this->actus->set($path, $alias, Path::MOD_PREPEND);

        return $this;
    }
}
