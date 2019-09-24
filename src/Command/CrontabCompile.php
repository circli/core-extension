<?php declare(strict_types=1);

namespace Circli\Core\Command;

use Circli\Contracts\PathContainer;
use FilesystemIterator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CrontabCompile extends Command
{
    protected static $defaultName = 'circli:crontab:compile';
    /** @var PathContainer */
    private $pathContainer;

    public function __construct(PathContainer $pathContainer)
    {
        parent::__construct();
        $this->pathContainer = $pathContainer;
    }

    public function run(InputInterface $input, OutputInterface $output)
    {
        $basePath = $this->pathContainer->getBasePath();
        if (!file_exists($basePath . '/cron.d')) {
            return;
        }

        $cron = [];
        $cron[] = '# File auto generated on ' . date('c');

        $it = new FilesystemIterator($basePath . '/cron.d');
        foreach ($it as $fileInfo) {
            $rows = file($fileInfo->getPathname(), FILE_IGNORE_NEW_LINES);
            foreach ($rows as $row) {
                $parts = explode(' ', $row);
                if (strpos($parts[0], '#') === 0) {
                    $cron[] = $row;
                }
                elseif ($parts[0] === 'spawn') {
                    unset($parts[0]);
                    $cron[] = '* * * * * APP_ENV={stage} /usr/local/bin/console-listener-spawner {domain}:{stage} ' . implode(' ', $parts);
                }
                elseif ($parts[5] === 'console') {
                    $parts[5] = 'APP_ENV={stage} ' . $basePath . '/vendor/bin/console';
                    $cron[] = implode(' ', $parts);
                }
            }
        }

        if (file_exists($basePath . '/crontab')) {
            $currentFile = file($basePath . '/crontab', FILE_IGNORE_NEW_LINES);
            unset($currentFile[0]);
            if (implode('', $currentFile) === implode('', $cron)) {
                $output->writeln('Skip update crontab no change');
                return 0;
            }
        }

        array_unshift($cron, '# File auto generated on ' . date('c'));
        file_put_contents($basePath . '/crontab', implode("\n", $cron) . "\n");
        return 0;
    }
}