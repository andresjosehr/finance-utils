<?php

namespace App\Console;

use App\Jobs\CollectP2PMarketDataJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // P2P Market Data Collection - Every minute
        $schedule->job(new CollectP2PMarketDataJob())
            ->everyMinute()
            ->name('collect-p2p-market-data')
            ->withoutOverlapping(10) // Prevent overlapping for up to 10 minutes
            ->onOneServer() // Run only on one server in multi-server setup
            ->runInBackground();

        // Optional: Clean up old failed jobs daily
        $schedule->command('queue:prune-failed --hours=72')
            ->daily()
            ->at('02:00')
            ->name('cleanup-failed-jobs');

        // Optional: Clean up old job batches daily
        $schedule->command('queue:prune-batches --hours=72')
            ->daily()
            ->at('02:15')
            ->name('cleanup-job-batches');

        // Optional: Restart queue workers daily to prevent memory leaks
        $schedule->command('queue:restart')
            ->daily()
            ->at('03:00')
            ->name('restart-queue-workers');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}