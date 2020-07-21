<?php

namespace Geeky\Historical;

use Geeky\Historical\Console\Commands\CreateHistoryModel;
use Geeky\Historical\Console\Commands\SyncHistoricalData;
use Illuminate\Support\Facades\DB;
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
                SyncHistoricalData::class
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
