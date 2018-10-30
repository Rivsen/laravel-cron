<?php
namespace Rswork\Laravel\Cron\Console;

use Illuminate\Support\Str;

class GenerateCrontabCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:generate
    {--all : ignore env, generate all cronjob}
    {--base-path= : laravel\'s base path, default is <info>base_path()</info>}
    {--log-path= : output log files\' save path, default is <info><base-path>/storage/logs</info>}
    {--default-run-user= : default run cron\'s user if not configured in command, default is <info>webapp</info>}
    {--tz= : this crontab\'s running timezone(<info>CRON_TZ=UTC</info>)}
    {--shell= : this crontab\'s running <info>SHELL=/bin/bash</info>}
    {--path-env= : this crontab\'s <info>PATH=/sbin:/bin:/usr/sbin:/usr/bin</info>}
    {--cron-mailto-user= : this crontab\'s mail user(<info>MAILTO=webapp</info>)}
    {--home= : this crontab\'s running <info>HOME=/</info>}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate crontab file from configured commands.';

    /**
     * Execute the console command.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        $commands = $this->getApplication()->all();
        $jobs = [];
        $defaultRunUser = $this->option('default-run-user') ?: 'webapp';

        foreach ($commands as $commandName => $command) {
            if (!($command instanceof BaseCommand)) {
                continue;
            }

            $cronJobs = $command->getCronJabs();

            if (!$cronJobs) {
                continue;
            }

            $env = $this->option('env') ?: config('app.env');
            $basePath = $this->option('base-path') ?: base_path();
            $logPath = $this->option('log-path') ?: $basePath . '/storage/logs';

            foreach ($cronJobs as $cronJob) {
                $type = isset($cronJob['type']) ? $cronJob['type'] : self::CRON_TYPE_CURRENT;
                $user = isset($cronJob['user']) ? $cronJob['user'] : $defaultRunUser;
                $logName = isset($cronJob['log_name']) ? $cronJob['log_name'] : $commandName;
                $job = null;

                $args = isset($cronJob['arguments']) ? implode(' ', $cronJob['arguments']) : '';

                switch ($type) {
                    case self::CRON_TYPE_CURRENT:
                        $job = "{$cronJob['schedule']} {$user} {$basePath}/artisan {$commandName}";

                        if ($args) {
                            $job .= " {$args}";
                        }

                        if ($env) {
                            $job .= " --env={$env}";
                        }
                        break;

                    case self::CRON_TYPE_SYSTEM:
                        if (!isset($cronJob['command'])) {
                            break;
                        }

                        if (!isset($cronJob['log_name'])) {
                            $logName = Str::slug($commandName);
                        }

                        $job = "{$cronJob['schedule']} {$user}";

                        if ($env) {
                            $job .= " LARAVEL_ENV={$env}";
                        }

                        $job .= " {$cronJob['command']}";

                        if ($args) {
                            $job .= " {$args}";
                        }
                        break;

                    case self::CRON_TYPE_ARTISAN:
                        if (!isset($cronJob['command'])) {
                            break;
                        }

                        $job = "{$cronJob['schedule']} {$user} {$basePath}/artisan {$cronJob['command']}";

                        if ($args) {
                            $job .= " {$args}";
                        }

                        if ($env) {
                            $job .= " --env={$env}";
                        }
                        break;

                    default:
                }

                if (!$job) {
                    continue;
                }

                $job .= " >> {$logPath}/{$logName}.log";
                $underEnv = empty($cronJob['env']) ? ['local'] : $cronJob['env'];

                if (in_array($env, $underEnv, false) || $this->option('all')) {
                    $jobs[] = "# {$commandName}(". get_class($command) . ") {$command->getDescription()}";
                    $jobs[] = $job;
                    $jobs[] = '';
                }
            }
        }

        $tz = $this->option('tz') ?: date_default_timezone_get();
        $shell = $this->option('shell') ?: '/bin/bash';
        $path = $this->option('path-env') ?: '/sbin:/bin:/usr/sbin:/usr/bin';
        $mailto = $this->option('cron-mailto-user') ?: $defaultRunUser;
        $home = $this->option('home') ?: '/';

        $configs = [
            '# !!WARN!! DO NOT EDIT THIS FILE! PLEASE CONFIGURE CRONTAB IN LARAVEL COMMAND CLASS!',
            '',
            'CRON_TZ='.$tz,
            '',
            'SHELL='.$shell,
            'PATH='.$path,
            'MAILTO='.$mailto,
            'HOME='.$home,
            '',
            '# For details see man 4 crontabs',
            '',
            '# Example of job definition:',
            '# .---------------- minute (0 - 59)',
            '# |  .------------- hour (0 - 23)',
            '# |  |  .---------- day of month (1 - 31)',
            '# |  |  |  .------- month (1 - 12) OR jan,feb,mar,apr ...',
            '# |  |  |  |  .---- day of week (0 - 6) (Sunday=0 or 7) OR sun,mon,tue,wed,thu,fri,sat',
            '# |  |  |  |  |',
            '# *  *  *  *  * user-name command to be executed',
            '',
        ];

        foreach (array_merge($configs, $jobs) as $item) {
            $this->line($item);
        }

        $this->line('');
        $this->line('# Generated by ' . $this->getName() . ' command');
    }
}
