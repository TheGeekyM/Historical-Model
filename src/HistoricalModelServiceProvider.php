<?php

namespace Geeky\Historical;

use Geeky\Historical\Console\Commands\CreateHistoryModel;
use Illuminate\Support\ServiceProvider;

class HistoricalModelServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateHistoryModel::class,
            ]);
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {

    }
}
