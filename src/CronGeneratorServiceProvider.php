<?php
namespace Rswork\Laravel\Cron;

use Illuminate\Support\ServiceProvider;
use Rswork\Laravel\Cron\Console\GenerateCrontabCommand;

class CronGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('rswork.cron.generate', function() {
            return new GenerateCrontabCommand();
        });

        //$this->commands(['cron:generate']);
    }

    /**
     * Get the services provided by the provider
     *
     * @return array
     */
    public function provides()
    {
        return [
            'rswork.cron.generate'
        ];
    }
}