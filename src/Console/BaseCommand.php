<?php
namespace Rswork\Laravel\Cron\Console;

use Illuminate\Console\Command;

abstract class BaseCommand extends Command
{
    // System type command
    const CRON_TYPE_SYSTEM = 'system';

    // Current command
    const CRON_TYPE_CURRENT = 'current';

    // Laravel's commands
    const CRON_TYPE_ARTISAN = 'artisan';

    /**
     * Some examples
     *
     * @var array
     *
     *
        $cronTabs = [
            // 0 1 * * 1 webapp /path/to/laravel/artisan current:command -vvv >> /path/to/laravel/storage/logs/current:command.log
            [
                'schedule' => '0 1 * * 1',
                'arguments' => [
                    '-vvv',
                ],
                'env' => ['production', 'test'],
            ],
            // 0 1 * * 1 someone /bin/echo "hello" >> /path/to/laravel/storage/logs/echo.log
            [
                'schedule' => '0 1 * * 1',
                'command' => '/bin/echo "hello"',
                'type' => self::CRON_TYPE_SYSTEM,
                'user' => 'someone',
                'log_name' => 'echo',
                'env' => ['test'],
            ],
            // 0 1 * * 1 someone /path/to/laravel/artisan list -vvv >> /path/to/laravel/storage/logs/artisan:list.log
            [
                'schedule' => '0 1 * * 1',
                'command' => 'list',
                'type' => self::CRON_TYPE_ARTISAN,
                'user' => 'someone',
                'arguments' => [
                    '-vvv',
                ],
                'env' => ['cronjob'],
            ],
        ]
     */
    protected $cronJabs;

    /**
     * Get configured crontabs
     *
     * @return array
     * @throws \Exception
     */
    public function getCronJabs()
    {
        if (!is_array($this->cronJabs) || count($this->cronJabs) === 0) {
            return [];
        }

        foreach ($this->cronJabs as $cronTab) {
            if (!isset($cronTab['schedule'])) {
                throw new \Exception("Command's schedule not configured.");
            }
        }

        return $this->cronJabs;
    }
}
