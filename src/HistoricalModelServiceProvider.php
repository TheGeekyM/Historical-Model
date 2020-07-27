<?php

namespace Geeky\Historical;

use Geeky\Historical\Console\Commands\CreateHistoryModel;
use Geeky\Historical\Console\Commands\MigrateHistoricalCommand;
use Geeky\Historical\Console\Commands\SyncHistoricalData;
use Illuminate\Support\ServiceProvider;

class HistoricalModelServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateHistoryModel::class,
                SyncHistoricalData::class,
                MigrateHistoricalCommand::class,
            ]);
        }
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
    }
}
