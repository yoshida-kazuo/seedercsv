<?php

namespace Cerotechsys\Seedercsv\Providers;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Cerotechsys\Seedercsv\Console\Commands\SeederCsvCommand;

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
            $this->commands([
                SeederCsvCommand::class,
            ]);
        }
    }

}