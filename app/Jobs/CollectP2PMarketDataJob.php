<?php

namespace App\Jobs;

use App\Models\TradingPair;
use App\Services\P2PDataCollectionService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CollectP2PMarketDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300; // 5 minutes timeout

    public int $tries = 3;

    public int $maxRetries = 3;

    public array $backoff = [30, 60, 120]; // 30s, 1m, 2m backoff

    public function __construct(
        private ?int $tradingPairId = null,
        private bool $forceCollection = false
    ) {
        $this->onQueue('p2p-data-collection');
    }

    /**
     * Execute the job
     */
    public function handle(P2PDataCollectionService $collectionService): void
    {
        $startTime = microtime(true);

        Log::info('Starting P2P market data collection job', [
            'trading_pair_id' => $this->tradingPairId,
            'force_collection' => $this->forceCollection,
            'attempt' => $this->attempts(),
        ]);

        try {
            if ($this->tradingPairId) {
                // Collect data for specific trading pair
                $this->handleSinglePair($collectionService);
            } else {
                // Collect data for all pairs that need updating
                $this->handleAllPairs($collectionService);
            }

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('P2P market data collection job completed successfully', [
                'execution_time_ms' => $executionTime,
                'attempt' => $this->attempts(),
            ]);

        } catch (Exception $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::error('P2P market data collection job failed', [
                'error' => $e->getMessage(),
                'execution_time_ms' => $executionTime,
                'attempt' => $this->attempts(),
                'max_tries' => $this->tries,
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle collection for a single trading pair
     */
    private function handleSinglePair(P2PDataCollectionService $collectionService): void
    {
        $pair = TradingPair::findOrFail($this->tradingPairId);

        if (! $pair->is_active) {
            Log::warning('Skipping inactive trading pair', [
                'pair_id' => $pair->id,
                'pair_symbol' => $pair->pair_symbol,
            ]);

            return;
        }

        if (! $this->forceCollection && ! $pair->isCollectionDue()) {
            Log::debug('Trading pair collection not due yet', [
                'pair_id' => $pair->id,
                'pair_symbol' => $pair->pair_symbol,
                'last_collection' => $pair->latestSnapshot()?->collected_at,
                'interval_minutes' => $pair->collection_interval_minutes,
            ]);

            return;
        }

        $result = $collectionService->collectPairData($pair);

        if (! $result['success']) {
            throw new Exception(
                "Failed to collect data for pair {$pair->pair_symbol}: ".
                ($result['error'] ?? 'Unknown error')
            );
        }

        Log::info('Successfully collected data for trading pair', [
            'pair_symbol' => $pair->pair_symbol,
            'snapshots_created' => $result['snapshots_created'],
            'buy_snapshot_id' => $result['buy_snapshot_id'],
            'sell_snapshot_id' => $result['sell_snapshot_id'],
        ]);
    }

    /**
     * Handle collection for all trading pairs
     */
    private function handleAllPairs(P2PDataCollectionService $collectionService): void
    {
        $results = $collectionService->collectAllPairs();

        Log::info('Batch P2P data collection completed', $results);

        // If more than 50% of collections failed, consider it a job failure
        if ($results['total_pairs'] > 0) {
            $failureRate = $results['failed_collections'] / $results['total_pairs'];

            if ($failureRate > 0.5) {
                throw new Exception(
                    "High failure rate in batch collection: {$results['failed_collections']}/{$results['total_pairs']} failed"
                );
            }
        }

        // Dispatch individual jobs for any pairs that failed
        if (! empty($results['errors'])) {
            $this->retryFailedPairs($results['errors']);
        }
    }

    /**
     * Retry failed pairs with individual jobs
     */
    private function retryFailedPairs(array $errors): void
    {
        foreach ($errors as $error) {
            if (isset($error['pair'])) {
                $pair = TradingPair::where('pair_symbol', $error['pair'])->first();

                if ($pair) {
                    // Dispatch with delay to avoid overwhelming the API
                    CollectP2PMarketDataJob::dispatch($pair->id, true)
                        ->delay(now()->addMinutes(2))
                        ->onQueue('p2p-data-collection-retry');

                    Log::info('Scheduled retry for failed pair', [
                        'pair_symbol' => $pair->pair_symbol,
                        'original_error' => $error['error'],
                    ]);
                }
            }
        }
    }

    /**
     * Handle job failure
     */
    public function failed(Exception $exception): void
    {
        Log::error('P2P market data collection job permanently failed', [
            'trading_pair_id' => $this->tradingPairId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
            'max_tries' => $this->tries,
        ]);

        // Could send notification to administrators here
        // NotificationService::sendAlert('P2P Data Collection Failed', $exception->getMessage());
    }

    /**
     * Calculate the number of seconds to wait before retrying the job
     */
    public function backoff(): array
    {
        return $this->backoff;
    }

    /**
     * Determine if the job should be retried
     */
    public function shouldRetry(Exception $exception): bool
    {
        // Don't retry for certain types of errors
        $nonRetryableErrors = [
            'Invalid trading pair',
            'Trading pair not found',
            'API access forbidden',
        ];

        foreach ($nonRetryableErrors as $errorPattern) {
            if (str_contains($exception->getMessage(), $errorPattern)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get job tags for monitoring
     */
    public function tags(): array
    {
        $tags = ['p2p-collection'];

        if ($this->tradingPairId) {
            $tags[] = 'single-pair';
            $tags[] = "pair-{$this->tradingPairId}";
        } else {
            $tags[] = 'batch-collection';
        }

        if ($this->forceCollection) {
            $tags[] = 'forced';
        }

        return $tags;
    }
}
