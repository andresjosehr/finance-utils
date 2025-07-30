<?php

namespace App\Console\Commands;

use App\Models\P2PMarketSnapshot;
use App\Models\TradingPair;
use App\Services\P2PDataCollectionService;
use App\Services\BinanceP2PService;
use Illuminate\Console\Command;

class P2PMonitorCommand extends Command
{
    protected $signature = 'p2p:monitor
                           {--hours=24 : Hours to look back for statistics}
                           {--health : Show health status}
                           {--pairs : Show trading pairs status}
                           {--snapshots : Show recent snapshots}
                           {--quality : Show data quality analysis}
                           {--api : Show API status}
                           {--json : Output in JSON format}';

    protected $description = 'Monitor P2P data collection system';

    public function handle(
        P2PDataCollectionService $collectionService,
        BinanceP2PService $binanceService
    ): int {
        $hours = (int) $this->option('hours');
        $outputJson = $this->option('json');

        try {
            $data = [];

            if ($this->option('health') || !$this->hasAnyOption()) {
                $data['health'] = $this->getHealthStatus($collectionService);
                if (!$outputJson) $this->displayHealthStatus($data['health']);
            }

            if ($this->option('pairs') || !$this->hasAnyOption()) {
                $data['pairs'] = $this->getPairsStatus();
                if (!$outputJson) $this->displayPairsStatus($data['pairs']);
            }

            if ($this->option('snapshots')) {
                $data['snapshots'] = $this->getSnapshotsStatus($hours);
                if (!$outputJson) $this->displaySnapshotsStatus($data['snapshots']);
            }

            if ($this->option('quality')) {
                $data['quality'] = $this->getQualityAnalysis($hours);
                if (!$outputJson) $this->displayQualityAnalysis($data['quality']);
            }

            if ($this->option('api')) {
                $data['api'] = $binanceService->getHealthStatus();
                if (!$outputJson) $this->displayApiStatus($data['api']);
            }

            // Statistics are always included
            $data['statistics'] = $collectionService->getCollectionStats($hours);
            if (!$outputJson) $this->displayStatistics($data['statistics']);

            if ($outputJson) {
                $this->line(json_encode($data, JSON_PRETTY_PRINT));
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Check if any display option is set
     */
    private function hasAnyOption(): bool
    {
        return !($this->option('health') || $this->option('pairs') || 
                $this->option('snapshots') || $this->option('quality') || 
                $this->option('api'));
    }

    /**
     * Get health status
     */
    private function getHealthStatus(P2PDataCollectionService $collectionService): array
    {
        return $collectionService->getHealthStatus();
    }

    /**
     * Display health status
     */
    private function displayHealthStatus(array $health): void
    {
        $this->info('🏥 System Health Status');
        $this->line('─────────────────────');

        if ($health['is_healthy']) {
            $this->info('✅ System is healthy');
        } else {
            $this->error('❌ System has issues:');
            foreach ($health['issues'] as $issue) {
                $this->error("  • {$issue}");
            }
        }

        $this->newLine();
        $this->line("📊 Active trading pairs: {$health['active_pairs_count']}");
        $this->line("🕐 Last collection: {$health['last_collection_minutes_ago']} minutes ago");
        $this->line("📈 Recent collections: {$health['recent_collections_count']}");
        $this->line("⭐ Average quality: " . number_format($health['average_quality_score'], 3));
        $this->newLine();
    }

    /**
     * Get trading pairs status
     */
    private function getPairsStatus(): array
    {
        $pairs = TradingPair::with(['marketSnapshots' => function ($query) {
            $query->orderBy('collected_at', 'desc')->limit(1);
        }])->get();

        return $pairs->map(function ($pair) {
            $latestSnapshot = $pair->marketSnapshots->first();
            
            return [
                'id' => $pair->id,
                'symbol' => $pair->pair_symbol,
                'is_active' => $pair->is_active,
                'interval_minutes' => $pair->collection_interval_minutes,
                'last_collection' => $latestSnapshot?->collected_at,
                'minutes_since_last' => $latestSnapshot ? 
                    now()->diffInMinutes($latestSnapshot->collected_at) : null,
                'is_collection_due' => $pair->isCollectionDue(),
                'snapshots_count' => $pair->marketSnapshots()->count()
            ];
        })->toArray();
    }

    /**
     * Display trading pairs status
     */
    private function displayPairsStatus(array $pairs): void
    {
        $this->info('📊 Trading Pairs Status');
        $this->line('─────────────────────');

        $headers = ['Pair', 'Active', 'Interval', 'Last Collection', 'Due', 'Snapshots'];
        $rows = [];

        foreach ($pairs as $pair) {
            $rows[] = [
                $pair['symbol'],
                $pair['is_active'] ? '✅' : '❌',
                $pair['interval_minutes'] . 'm',
                $pair['last_collection'] ? 
                    $pair['last_collection'] . ' (' . $pair['minutes_since_last'] . 'm ago)' : 
                    'Never',
                $pair['is_collection_due'] ? '🔴 Yes' : '🟢 No',
                number_format($pair['snapshots_count'])
            ];
        }

        $this->table($headers, $rows);
        $this->newLine();
    }

    /**
     * Get snapshots status
     */
    private function getSnapshotsStatus(int $hours): array
    {
        $snapshots = P2PMarketSnapshot::with('tradingPair')
            ->where('collected_at', '>=', now()->subHours($hours))
            ->orderBy('collected_at', 'desc')
            ->limit(20)
            ->get();

        return $snapshots->map(function ($snapshot) {
            return [
                'id' => $snapshot->id,
                'pair' => $snapshot->tradingPair->pair_symbol,
                'trade_type' => $snapshot->trade_type,
                'collected_at' => $snapshot->collected_at,
                'total_ads' => $snapshot->total_ads,
                'quality_score' => $snapshot->data_quality_score
            ];
        })->toArray();
    }

    /**
     * Display snapshots status
     */
    private function displaySnapshotsStatus(array $snapshots): void
    {
        $this->info('📸 Recent Snapshots');
        $this->line('─────────────────');

        if (empty($snapshots)) {
            $this->warn('No snapshots found');
            return;
        }

        $headers = ['ID', 'Pair', 'Type', 'Time', 'Ads', 'Quality'];
        $rows = [];

        foreach ($snapshots as $snapshot) {
            $qualityIcon = $snapshot['quality_score'] >= 0.8 ? '🟢' : 
                          ($snapshot['quality_score'] >= 0.5 ? '🟡' : '🔴');
            
            $rows[] = [
                $snapshot['id'],
                $snapshot['pair'],
                $snapshot['trade_type'],
                $snapshot['collected_at'],
                $snapshot['total_ads'],
                $qualityIcon . ' ' . number_format($snapshot['quality_score'], 3)
            ];
        }

        $this->table($headers, $rows);
        $this->newLine();
    }

    /**
     * Get quality analysis
     */
    private function getQualityAnalysis(int $hours): array
    {
        $snapshots = P2PMarketSnapshot::where('collected_at', '>=', now()->subHours($hours))
            ->get();

        $qualityBuckets = [
            'excellent' => $snapshots->where('data_quality_score', '>=', 0.9)->count(),
            'good' => $snapshots->whereBetween('data_quality_score', [0.7, 0.9])->count(),
            'fair' => $snapshots->whereBetween('data_quality_score', [0.5, 0.7])->count(),
            'poor' => $snapshots->where('data_quality_score', '<', 0.5)->count(),
        ];

        $byTradeType = [
            'BUY' => [
                'count' => $snapshots->where('trade_type', 'BUY')->count(),
                'avg_quality' => $snapshots->where('trade_type', 'BUY')->avg('data_quality_score'),
                'avg_ads' => $snapshots->where('trade_type', 'BUY')->avg('total_ads')
            ],
            'SELL' => [
                'count' => $snapshots->where('trade_type', 'SELL')->count(),
                'avg_quality' => $snapshots->where('trade_type', 'SELL')->avg('data_quality_score'),
                'avg_ads' => $snapshots->where('trade_type', 'SELL')->avg('total_ads')
            ]
        ];

        return [
            'total_snapshots' => $snapshots->count(),
            'quality_buckets' => $qualityBuckets,
            'by_trade_type' => $byTradeType,
            'overall_avg_quality' => $snapshots->avg('data_quality_score'),
            'overall_avg_ads' => $snapshots->avg('total_ads')
        ];
    }

    /**
     * Display quality analysis
     */
    private function displayQualityAnalysis(array $quality): void
    {
        $this->info('⭐ Data Quality Analysis');
        $this->line('─────────────────────');

        $this->line("Total snapshots: {$quality['total_snapshots']}");
        $this->line("Overall avg quality: " . number_format($quality['overall_avg_quality'], 3));
        $this->line("Overall avg ads: " . number_format($quality['overall_avg_ads'], 1));
        $this->newLine();

        $this->line('Quality Distribution:');
        $buckets = $quality['quality_buckets'];
        $this->line("  🟢 Excellent (≥0.9): {$buckets['excellent']}");
        $this->line("  🟡 Good (0.7-0.9): {$buckets['good']}");
        $this->line("  🟠 Fair (0.5-0.7): {$buckets['fair']}");
        $this->line("  🔴 Poor (<0.5): {$buckets['poor']}");
        $this->newLine();

        $this->line('By Trade Type:');
        foreach ($quality['by_trade_type'] as $type => $stats) {
            $this->line("  {$type}: {$stats['count']} snapshots, " . 
                       "avg quality: " . number_format($stats['avg_quality'], 3) . ", " .
                       "avg ads: " . number_format($stats['avg_ads'], 1));
        }
        $this->newLine();
    }

    /**
     * Display API status
     */
    private function displayApiStatus(array $api): void
    {
        $this->info('🌐 API Status');
        $this->line('─────────────');

        $this->line("URL: {$api['api_url']}");
        $this->newLine();

        $rateLimit = $api['rate_limit'];
        $this->line('Rate Limiting:');
        $this->line("  Max attempts: {$rateLimit['max_attempts']}/minute");
        $this->line("  Remaining: {$rateLimit['remaining']}");
        $this->line("  Reset in: {$rateLimit['reset_in_seconds']} seconds");
        $this->line("  Is limited: " . ($rateLimit['is_limited'] ? '🔴 Yes' : '🟢 No'));
        $this->newLine();

        $retry = $api['retry_config'];
        $this->line('Retry Configuration:');
        $this->line("  Max attempts: {$retry['max_attempts']}");
        $this->line("  Delay: {$retry['delay_ms']}ms");
        $this->newLine();

        $this->line("Cache TTL: {$api['cache_ttl_seconds']} seconds");
        $this->newLine();
    }

    /**
     * Display statistics
     */
    private function displayStatistics(array $stats): void
    {
        $this->info('📈 Collection Statistics (Last ' . $stats['period_hours'] . ' hours)');
        $this->line('────────────────────────────────────');

        $this->line("Total snapshots: {$stats['total_snapshots']}");
        $this->line("BUY snapshots: {$stats['by_trade_type']['BUY']}");
        $this->line("SELL snapshots: {$stats['by_trade_type']['SELL']}");
        $this->newLine();

        $this->line("High quality: {$stats['by_quality']['high']}");
        $this->line("Medium quality: {$stats['by_quality']['medium']}");
        $this->line("Low quality: {$stats['by_quality']['low']}");
        $this->newLine();

        $this->line("Average quality score: " . number_format($stats['average_quality_score'], 4));
        $this->line("Total ads collected: " . number_format($stats['total_ads_collected']));
        $this->line("Unique pairs: {$stats['unique_pairs']}");
        $this->line("Recent collections/hour: {$stats['recent_collections_per_hour']}");
        $this->line("Quality trend: " . number_format($stats['quality_trend'], 4));
        $this->newLine();
    }
}