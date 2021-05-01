<?php

namespace Cerotechsys\Seedercsv\Providers;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Cerotechsys\Seedercsv\Console\Commands\SeederCsvCommand;
use Cerotechsys\Seedercsv\Console\Commands\LegacySeederCsvCommand;

class ServiceProvider extends BaseServiceProvider
{

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

        if ($this->app->runningInConsole()) {

            /** migration file */
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

            /** Attach command */
            $commands = [
                SeederCsvCommand::class,
            ];
            if (stripos($this->app->version(), '6') === 0) {
                $commands = [
                    LegacySeederCsvCommand::class,
                ];
            }
            $this->commands($commands);

        }

    }

}