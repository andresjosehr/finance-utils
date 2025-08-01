<?php

namespace App\Services;

use App\Models\P2PMarketSnapshot;
use App\Models\TradingPair;
use Exception;
use Illuminate\Support\Facades\Log;

class P2PDataCollectionService
{
    public function __construct(
        private BinanceP2PService $binanceService,
        private P2PDataValidationService $validationService
    ) {}

    /**
     * Collect data for all active trading pairs
     */
    public function collectAllPairs(): array
    {
        $results = [
            'total_pairs' => 0,
            'successful_collections' => 0,
            'failed_collections' => 0,
            'pairs_processed' => [],
            'errors' => [],
        ];

        $pairs = TradingPair::needingCollection();
        $results['total_pairs'] = $pairs->count();

        Log::info('Starting P2P data collection batch', [
            'pairs_count' => $results['total_pairs'],
            'pairs' => $pairs->pluck('pair_symbol')->toArray(),
        ]);

        foreach ($pairs as $pair) {
            try {
                $pairResult = $this->collectPairData($pair);

                if ($pairResult['success']) {
                    $results['successful_collections']++;
                } else {
                    $results['failed_collections']++;
                    $results['errors'][] = [
                        'pair' => $pair->pair_symbol,
                        'error' => $pairResult['error'],
                    ];
                }

                $results['pairs_processed'][] = $pairResult;

            } catch (Exception $e) {
                $results['failed_collections']++;
                $results['errors'][] = [
                    'pair' => $pair->pair_symbol,
                    'error' => $e->getMessage(),
                ];

                Log::error('Failed to collect data for trading pair', [
                    'pair' => $pair->pair_symbol,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('P2P data collection batch completed', [
            'total_pairs' => $results['total_pairs'],
            'successful' => $results['successful_collections'],
            'failed' => $results['failed_collections'],
        ]);

        return $results;
    }

    /**
     * Collect data for a specific trading pair
     */
    public function collectPairData(TradingPair $pair): array
    {
        $result = [
            'pair' => $pair->pair_symbol,
            'success' => false,
            'snapshots_created' => 0,
            'buy_snapshot_id' => null,
            'sell_snapshot_id' => null,
            'error' => null,
            'collection_time' => now(),
            'metrics' => [],
        ];

        Log::debug('Collecting data for trading pair', [
            'pair' => $pair->pair_symbol,
            'last_collection' => $pair->latestSnapshot('BUY')?->collected_at,
        ]);

        try {
            // Determine collection strategy based on pair configuration
            $volumeRanges = $pair->getEffectiveVolumeRanges();
            $sampleVolume = $pair->getEffectiveSampleVolume();

            Log::debug('P2P Data Collection Debug', [
                'pair' => $pair->pair_symbol,
                'use_volume_sampling' => $pair->use_volume_sampling,
                'volume_ranges' => $volumeRanges,
                'sample_volume' => $sampleVolume,
                'should_use_sampling' => $pair->shouldUseVolumeSampling(),
            ]);

            // Collect buy and sell data with appropriate strategy
            $buyData = $this->binanceService->getP2PData(
                $pair->asset,
                $pair->fiat,
                'BUY',
                1,
                50,
                $pair->shouldUseVolumeSampling() ? null : $sampleVolume,
                $volumeRanges
            );

            $sellData = $this->binanceService->getP2PData(
                $pair->asset,
                $pair->fiat,
                'SELL',
                1,
                50,
                $pair->shouldUseVolumeSampling() ? null : $sampleVolume,
                $volumeRanges
            );

            // Process buy data
            if ($buyData) {
                $buySnapshot = $this->createSnapshot($pair, 'BUY', $buyData);
                if ($buySnapshot) {
                    $result['snapshots_created']++;
                    $result['buy_snapshot_id'] = $buySnapshot->id;
                    $result['metrics']['buy'] = $buySnapshot->getPriceMetrics();
                }
            }

            // Process sell data
            if ($sellData) {
                $sellSnapshot = $this->createSnapshot($pair, 'SELL', $sellData);
                if ($sellSnapshot) {
                    $result['snapshots_created']++;
                    $result['sell_snapshot_id'] = $sellSnapshot->id;
                    $result['metrics']['sell'] = $sellSnapshot->getPriceMetrics();
                }
            }

            // Calculate market spread if we have both buy and sell data
            if (isset($result['metrics']['buy']) && isset($result['metrics']['sell'])) {
                $result['metrics']['market_spread'] = $this->calculateMarketSpread(
                    $result['metrics']['buy'],
                    $result['metrics']['sell']
                );
            }

            $result['success'] = $result['snapshots_created'] > 0;

            if (! $result['success']) {
                $result['error'] = 'No data collected from API';
            }

        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
            Log::error('Error collecting P2P data', [
                'pair' => $pair->pair_symbol,
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * Create a market snapshot from API data
     */
    private function createSnapshot(TradingPair $pair, string $tradeType, array $apiData): ?P2PMarketSnapshot
    {
        try {
            // Validate the API data
            $validationResult = $this->validationService->validateApiData($apiData, $tradeType);

            if (! $validationResult['is_valid']) {
                Log::warning('Invalid API data received', [
                    'pair' => $pair->pair_symbol,
                    'trade_type' => $tradeType,
                    'validation_errors' => $validationResult['errors'],
                ]);

                // Still proceed with data collection but mark lower quality
            }

            // Extract collection metadata
            $metadata = [
                'api_response_time' => microtime(true),
                'validation_result' => $validationResult,
                'api_code' => $apiData['code'] ?? null,
                'api_message' => $apiData['message'] ?? null,
                'total_count' => $apiData['total'] ?? 0,
            ];

            // Create the snapshot
            $snapshot = P2PMarketSnapshot::create([
                'trading_pair_id' => $pair->id,
                'trade_type' => $tradeType,
                'collected_at' => now(),
                'raw_data' => $apiData,
                'total_ads' => count($apiData['data'] ?? []),
                'collection_metadata' => $metadata,
            ]);

            // Calculate and update quality score
            $snapshot->updateQualityScore();

            Log::debug('Created market snapshot', [
                'snapshot_id' => $snapshot->id,
                'pair' => $pair->pair_symbol,
                'trade_type' => $tradeType,
                'ads_count' => $snapshot->total_ads,
                'quality_score' => $snapshot->data_quality_score,
            ]);

            return $snapshot;

        } catch (Exception $e) {
            Log::error('Failed to create market snapshot', [
                'pair' => $pair->pair_symbol,
                'trade_type' => $tradeType,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Calculate market spread between buy and sell prices
     */
    private function calculateMarketSpread(array $buyMetrics, array $sellMetrics): array
    {
        $spread = [
            'absolute_spread' => 0,
            'percentage_spread' => 0,
            'arbitrage_opportunity' => false,
            'liquidity_ratio' => 0,
        ];

        $buyBestPrice = $buyMetrics['best'] ?? $buyMetrics['best_price'] ?? 0;
        $sellBestPrice = $sellMetrics['best'] ?? $sellMetrics['best_price'] ?? 0;

        if ($buyBestPrice > 0 && $sellBestPrice > 0) {
            $spread['absolute_spread'] = round($sellBestPrice - $buyBestPrice, 2);
            $spread['percentage_spread'] = round(
                ($spread['absolute_spread'] / $buyBestPrice) * 100,
                4
            );
            $spread['arbitrage_opportunity'] = $spread['absolute_spread'] > 0;
        }

        // Calculate liquidity ratio (buy volume / sell volume)
        if (isset($buyMetrics['total_volume']) && isset($sellMetrics['total_volume'])) {
            $buyVolume = $buyMetrics['total_volume'];
            $sellVolume = $sellMetrics['total_volume'];

            if ($sellVolume > 0) {
                $spread['liquidity_ratio'] = round($buyVolume / $sellVolume, 4);
            }
        }

        return $spread;
    }

    /**
     * Get collection statistics for monitoring
     */
    public function getCollectionStats(int $hours = 24): array
    {
        $since = now()->subHours($hours);

        $snapshots = P2PMarketSnapshot::where('collected_at', '>=', $since)->get();

        $stats = [
            'period_hours' => $hours,
            'total_snapshots' => $snapshots->count(),
            'by_trade_type' => [
                'BUY' => $snapshots->where('trade_type', 'BUY')->count(),
                'SELL' => $snapshots->where('trade_type', 'SELL')->count(),
            ],
            'by_quality' => [
                'high' => $snapshots->where('data_quality_score', '>=', 0.8)->count(),
                'medium' => $snapshots->whereBetween('data_quality_score', [0.5, 0.8])->count(),
                'low' => $snapshots->where('data_quality_score', '<', 0.5)->count(),
            ],
            'average_quality_score' => round($snapshots->avg('data_quality_score'), 4),
            'total_ads_collected' => $snapshots->sum('total_ads'),
            'unique_pairs' => $snapshots->groupBy('trading_pair_id')->count(),
        ];

        // Recent collection frequency
        $recentSnapshots = $snapshots->where('collected_at', '>=', now()->subHour());
        $stats['recent_collections_per_hour'] = $recentSnapshots->count();

        // Quality trend
        $qualityTrend = $snapshots->sortBy('collected_at')
            ->take(-10)
            ->avg('data_quality_score');
        $stats['quality_trend'] = round($qualityTrend, 4);

        return $stats;
    }

    /**
     * Clean up old snapshots based on retention policy
     */
    public function cleanupOldSnapshots(int $daysToKeep = 30): int
    {
        $cutoffDate = now()->subDays($daysToKeep);

        $deletedCount = P2PMarketSnapshot::where('collected_at', '<', $cutoffDate)->delete();

        if ($deletedCount > 0) {
            Log::info('Cleaned up old P2P snapshots', [
                'deleted_count' => $deletedCount,
                'cutoff_date' => $cutoffDate,
                'days_kept' => $daysToKeep,
            ]);
        }

        return $deletedCount;
    }

    /**
     * Get health status of the data collection system
     */
    public function getHealthStatus(): array
    {
        $binanceHealth = $this->binanceService->getHealthStatus();
        $collectionStats = $this->getCollectionStats(1); // Last hour

        $latestSnapshot = P2PMarketSnapshot::orderBy('collected_at', 'desc')->first();
        $minutesSinceLastCollection = $latestSnapshot
            ? now()->diffInMinutes($latestSnapshot->collected_at)
            : null;

        $isHealthy = true;
        $issues = [];

        // Check if collections are happening regularly
        if ($minutesSinceLastCollection && $minutesSinceLastCollection > 10) {
            $isHealthy = false;
            $issues[] = "No data collected in {$minutesSinceLastCollection} minutes";
        }

        // Check if we have recent low-quality data
        if ($collectionStats['average_quality_score'] < 0.5) {
            $isHealthy = false;
            $issues[] = "Low average quality score: {$collectionStats['average_quality_score']}";
        }

        // Check API rate limiting
        if ($binanceHealth['rate_limit']['is_limited']) {
            $isHealthy = false;
            $issues[] = 'API rate limited';
        }

        return [
            'is_healthy' => $isHealthy,
            'issues' => $issues,
            'last_collection_minutes_ago' => $minutesSinceLastCollection,
            'recent_collections_count' => $collectionStats['total_snapshots'],
            'average_quality_score' => $collectionStats['average_quality_score'],
            'binance_api' => $binanceHealth,
            'active_pairs_count' => TradingPair::active()->count(),
        ];
    }
}
