<?php

namespace App\Console\Commands;

use App\Jobs\CollectP2PMarketDataJob;
use App\Models\TradingPair;
use App\Services\P2PDataCollectionService;
use Illuminate\Console\Command;

class CollectP2PDataCommand extends Command
{
    protected $signature = 'p2p:collect
                           {--pair= : Specific trading pair symbol (e.g., USDT/VES)}
                           {--asset= : Asset symbol to collect (e.g., USDT)}
                           {--fiat= : Fiat symbol to collect (e.g., VES)}
                           {--force : Force collection even if not due}
                           {--sync : Run synchronously instead of dispatching job}
                           {--queue= : Queue name to dispatch job to}';

    protected $description = 'Collect P2P market data from Binance';

    public function handle(P2PDataCollectionService $collectionService): int
    {
        $this->info('🚀 Starting P2P market data collection...');

        try {
            // Determine which pairs to collect
            $pairs = $this->getPairsToCollect();

            if ($pairs->isEmpty()) {
                $this->warn('⚠️  No trading pairs found matching the criteria');

                return self::SUCCESS;
            }

            $this->info("📊 Found {$pairs->count()} trading pair(s) to process:");
            foreach ($pairs as $pair) {
                $this->line("  - {$pair->pair_symbol} (ID: {$pair->id})");
            }

            if ($this->option('sync')) {
                // Run synchronously
                $this->collectSynchronously($collectionService, $pairs);
            } else {
                // Dispatch jobs
                $this->dispatchJobs($pairs);
            }

            $this->info('✅ P2P data collection completed successfully!');

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Get trading pairs to collect based on options
     */
    private function getPairsToCollect()
    {
        $query = TradingPair::query();

        if ($pairSymbol = $this->option('pair')) {
            $query->where('pair_symbol', strtoupper($pairSymbol));
        } elseif ($asset = $this->option('asset')) {
            $query->where('asset', strtoupper($asset));

            if ($fiat = $this->option('fiat')) {
                $query->where('fiat', strtoupper($fiat));
            }
        } else {
            // Get all active pairs
            $query->where('is_active', true);

            if (! $this->option('force')) {
                // Only get pairs that need collection
                $allPairs = $query->get();

                return $allPairs->filter(fn ($pair) => $pair->isCollectionDue());
            }
        }

        return $query->get();
    }

    /**
     * Collect data synchronously
     */
    private function collectSynchronously(P2PDataCollectionService $collectionService, $pairs): void
    {
        $this->info('🔄 Running synchronous collection...');

        $progressBar = $this->output->createProgressBar($pairs->count());
        $progressBar->start();

        $totalSuccess = 0;
        $totalFailed = 0;

        foreach ($pairs as $pair) {
            try {
                $result = $collectionService->collectPairData($pair);

                if ($result['success']) {
                    $totalSuccess++;
                    $this->newLine();
                    $this->info("✅ {$pair->pair_symbol}: {$result['snapshots_created']} snapshots created");
                } else {
                    $totalFailed++;
                    $this->newLine();
                    $this->error("❌ {$pair->pair_symbol}: {$result['error']}");
                }

            } catch (\Exception $e) {
                $totalFailed++;
                $this->newLine();
                $this->error("❌ {$pair->pair_symbol}: {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("📈 Results: {$totalSuccess} successful, {$totalFailed} failed");
    }

    /**
     * Dispatch collection jobs
     */
    private function dispatchJobs($pairs): void
    {
        $this->info('📤 Dispatching collection jobs...');

        $queueName = $this->option('queue') ?: 'p2p-data-collection';
        $force = $this->option('force');

        foreach ($pairs as $pair) {
            CollectP2PMarketDataJob::dispatch($pair->id, $force)
                ->onQueue($queueName);

            $this->line("  ➤ Dispatched job for {$pair->pair_symbol}");
        }

        $this->info("✅ Dispatched {$pairs->count()} job(s) to '{$queueName}' queue");
        $this->comment('💡 Run "php artisan queue:work" to process the jobs');
    }
}
