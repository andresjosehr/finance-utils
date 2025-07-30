<?php

use App\Jobs\CollectP2PMarketDataJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule P2P data collection with dynamic intervals based on trading pairs
// Check every minute for pairs that need collection, but respect individual intervals
Schedule::call(function () {
    $pairsNeedingCollection = \App\Models\TradingPair::needingCollection();
    
    if ($pairsNeedingCollection->isEmpty()) {
        return; // No pairs need collection right now
    }
    
    // Group pairs by collection interval to optimize job dispatching
    $pairsByInterval = $pairsNeedingCollection->groupBy('collection_interval_minutes');
    
    foreach ($pairsByInterval as $intervalMinutes => $pairs) {
        foreach ($pairs as $pair) {
            \App\Jobs\CollectP2PMarketDataJob::dispatch($pair->id, false)
                ->onQueue('p2p-data-collection');
        }
    }
    
    \Illuminate\Support\Facades\Log::info('Dispatched P2P collection jobs', [
        'total_pairs' => $pairsNeedingCollection->count(),
        'pairs_by_interval' => $pairsByInterval->map->count()->toArray()
    ]);
})
    ->everyMinute()
    ->name('collect-p2p-data')
    ->withoutOverlapping(10) // Prevent overlapping executions, timeout after 10 minutes
    ->onOneServer(); // Only run on one server in multi-server setup

// Schedule cleanup of old snapshots daily at 2 AM
Schedule::call(function () {
    $collectionService = app(\App\Services\P2PDataCollectionService::class);
    $deletedCount = $collectionService->cleanupOldSnapshots(30); // Keep 30 days
    
    if ($deletedCount > 0) {
        \Illuminate\Support\Facades\Log::info('Scheduled cleanup completed', [
            'deleted_snapshots' => $deletedCount
        ]);
    }
})
    ->dailyAt('02:00')
    ->name('cleanup-old-snapshots')
    ->onOneServer();
    // Note: runInBackground() cannot be used with closures

// Schedule health check every hour
Schedule::call(function () {
    $collectionService = app(\App\Services\P2PDataCollectionService::class);
    $health = $collectionService->getHealthStatus();
    
    if (!$health['is_healthy']) {
        \Illuminate\Support\Facades\Log::warning('P2P system health check failed', [
            'issues' => $health['issues'],
            'last_collection_minutes_ago' => $health['last_collection_minutes_ago']
        ]);
        
        // Could send notifications here
        // NotificationService::sendHealthAlert($health);
    }
})
    ->hourly()
    ->name('p2p-health-check')
    ->onOneServer();
    // Note: runInBackground() cannot be used with closures