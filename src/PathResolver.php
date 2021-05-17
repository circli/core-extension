<?php declare(strict_types=1);

namespace Circli\Core;

use Actus\Path;

class PathResolver
{
    /** @var string[] */
    protected array $ns = [
        'config:',
        'resources:',
        'template:',
    ];

    public function __construct(
        private Path $path,
    ) {}

    public function get(string $path): ?string
    {
        $realPath = $this->path->get($path);

        [$alias, $filePath] = explode(':', $path);
        if (!$realPath && strpos($filePath, '/')) {
            [$moduleAlias, $filePath] = explode('/', $filePath, 2);
            $modulePath = $moduleAlias . '-' . $alias . ':' . $filePath;
            $realPath = $this->path->get($modulePath);
        }

        return $realPath;
    }

    public function add(string $module, string $ns, string $path): static
    {
        $alias = $module . '-' . $ns;

        $this->path->set($path, $alias, Path::MOD_PREPEND);

        return $this;
    }
}
