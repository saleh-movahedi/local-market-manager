<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Schedule jobs
        $this->app->booted(function () {
            $schedule = app(Schedule::class);

            // Sync all stores products daily at 03:00
            $schedule->job(\App\Jobs\SyncAllStoresProducts::class)
                ->dailyAt('03:00');

            // Calculate weekly settlements every Monday at 04:00
            $schedule->job(\App\Jobs\CalculateSettlements::class)
                ->weeklyOn(1, '04:00');

            // Horizon snapshot every 5 minutes
            $schedule->command('horizon:snapshot')->everyFiveMinutes();
        });
    }
}