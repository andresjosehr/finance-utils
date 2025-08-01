<?php

namespace App\Http\Controllers;

use App\Services\BinanceP2PService;
use App\Services\StatisticalAnalysisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BinanceP2PController extends Controller
{
    public function __construct(
        private BinanceP2PService $binanceP2PService,
        private StatisticalAnalysisService $statisticalAnalysisService
    ) {}

    public function getMarketSummary(Request $request): JsonResponse
    {
        $asset = $request->get('asset', 'USDT');
        $fiat = $request->get('fiat', 'VES');
        $rows = min((int) $request->get('rows', 20), 50); // Limitar máximo a 50

        $summary = $this->binanceP2PService->getMarketSummary($asset, $fiat, $rows);

        return response()->json($summary);
    }

    public function getBuyPrices(Request $request): JsonResponse
    {
        $asset = $request->get('asset', 'USDT');
        $fiat = $request->get('fiat', 'VES');
        $rows = min((int) $request->get('rows', 20), 50);

        $data = $this->binanceP2PService->getBuyPrices($asset, $fiat, $rows);

        if ($data === null) {
            return response()->json(['error' => 'Failed to fetch buy prices'], 500);
        }

        $metrics = $this->binanceP2PService->calculatePriceMetrics($data);

        return response()->json([
            'type' => 'buy',
            'asset' => $asset,
            'fiat' => $fiat,
            'metrics' => $metrics,
            'data' => $data,
        ]);
    }

    public function getSellPrices(Request $request): JsonResponse
    {
        $asset = $request->get('asset', 'USDT');
        $fiat = $request->get('fiat', 'VES');
        $rows = min((int) $request->get('rows', 20), 50);

        $data = $this->binanceP2PService->getSellPrices($asset, $fiat, $rows);

        if ($data === null) {
            return response()->json(['error' => 'Failed to fetch sell prices'], 500);
        }

        $metrics = $this->binanceP2PService->calculatePriceMetrics($data);

        return response()->json([
            'type' => 'sell',
            'asset' => $asset,
            'fiat' => $fiat,
            'metrics' => $metrics,
            'data' => $data,
        ]);
    }

    public function getBothPrices(Request $request): JsonResponse
    {
        $asset = $request->get('asset', 'USDT');
        $fiat = $request->get('fiat', 'VES');
        $rows = min((int) $request->get('rows', 20), 50);

        $data = $this->binanceP2PService->getBothPrices($asset, $fiat, $rows);

        $buyMetrics = $data['buy'] ? $this->binanceP2PService->calculatePriceMetrics($data['buy']) : null;
        $sellMetrics = $data['sell'] ? $this->binanceP2PService->calculatePriceMetrics($data['sell']) : null;

        return response()->json([
            'asset' => $asset,
            'fiat' => $fiat,
            'timestamp' => now()->toISOString(),
            'buy' => [
                'metrics' => $buyMetrics,
                'data' => $data['buy'],
            ],
            'sell' => [
                'metrics' => $sellMetrics,
                'data' => $data['sell'],
            ],
        ]);
    }

    public function getP2PData(Request $request): JsonResponse
    {
        $asset = $request->get('asset', 'USDT');
        $fiat = $request->get('fiat', 'VES');
        $tradeType = strtoupper($request->get('trade_type', 'BUY'));
        $page = max(1, (int) $request->get('page', 1));
        $rows = min((int) $request->get('rows', 20), 50);
        $transAmount = $request->get('trans_amount') ? (float) $request->get('trans_amount') : null;

        if (! in_array($tradeType, ['BUY', 'SELL'])) {
            return response()->json(['error' => 'Invalid trade_type. Must be BUY or SELL'], 400);
        }

        $data = $this->binanceP2PService->getP2PData($asset, $fiat, $tradeType, $page, $rows, $transAmount);

        if ($data === null) {
            return response()->json(['error' => 'Failed to fetch P2P data'], 500);
        }

        return response()->json($data);
    }

    /**
     * Get advanced statistical analysis for market data
     */
    public function getStatisticalAnalysis(Request $request): JsonResponse
    {
        $asset = $request->get('asset', 'USDT');
        $fiat = $request->get('fiat', 'VES');
        $tradeType = strtoupper($request->get('trade_type', 'BUY'));
        $rows = min((int) $request->get('rows', 50), 100);
        $outlierMethod = $request->get('outlier_method', 'iqr');
        $confidenceLevel = (float) $request->get('confidence_level', 0.95);

        if (! in_array($tradeType, ['BUY', 'SELL'])) {
            return response()->json(['error' => 'Invalid trade_type. Must be BUY or SELL'], 400);
        }

        if (! in_array($outlierMethod, ['iqr', 'zscore', 'modified_zscore'])) {
            return response()->json(['error' => 'Invalid outlier_method. Must be iqr, zscore, or modified_zscore'], 400);
        }

        if (! in_array($confidenceLevel, [0.90, 0.95, 0.99])) {
            return response()->json(['error' => 'Invalid confidence_level. Must be 0.90, 0.95, or 0.99'], 400);
        }

        $data = $this->binanceP2PService->getP2PData($asset, $fiat, $tradeType, 1, $rows);

        if ($data === null) {
            return response()->json(['error' => 'Failed to fetch P2P data'], 500);
        }

        $analysis = $this->statisticalAnalysisService->analyzeMarketData($data, [
            'outlier_method' => $outlierMethod,
            'confidence_level' => $confidenceLevel,
        ]);

        return response()->json([
            'asset' => $asset,
            'fiat' => $fiat,
            'trade_type' => $tradeType,
            'timestamp' => now()->toISOString(),
            'analysis' => $analysis,
            'metadata' => [
                'sample_size' => $rows,
                'outlier_method' => $outlierMethod,
                'confidence_level' => $confidenceLevel,
            ],
        ]);
    }

    /**
     * Get comprehensive market analysis with both buy and sell statistics
     */
    public function getComprehensiveAnalysis(Request $request): JsonResponse
    {
        $asset = $request->get('asset', 'USDT');
        $fiat = $request->get('fiat', 'VES');
        $rows = min((int) $request->get('rows', 50), 100);
        $outlierMethod = $request->get('outlier_method', 'iqr');
        $confidenceLevel = (float) $request->get('confidence_level', 0.95);

        if (! in_array($outlierMethod, ['iqr', 'zscore', 'modified_zscore'])) {
            return response()->json(['error' => 'Invalid outlier_method. Must be iqr, zscore, or modified_zscore'], 400);
        }

        if (! in_array($confidenceLevel, [0.90, 0.95, 0.99])) {
            return response()->json(['error' => 'Invalid confidence_level. Must be 0.90, 0.95, or 0.99'], 400);
        }

        $data = $this->binanceP2PService->getBothPrices($asset, $fiat, $rows);

        $analysis = [
            'asset' => $asset,
            'fiat' => $fiat,
            'timestamp' => now()->toISOString(),
            'buy_analysis' => null,
            'sell_analysis' => null,
            'market_comparison' => null,
        ];

        $options = [
            'outlier_method' => $outlierMethod,
            'confidence_level' => $confidenceLevel,
        ];

        if ($data['buy']) {
            $analysis['buy_analysis'] = $this->statisticalAnalysisService->analyzeMarketData($data['buy'], $options);
        }

        if ($data['sell']) {
            $analysis['sell_analysis'] = $this->statisticalAnalysisService->analyzeMarketData($data['sell'], $options);
        }

        // Compare buy and sell markets
        if ($data['buy'] && $data['sell']) {
            $analysis['market_comparison'] = $this->compareMarkets($analysis['buy_analysis'], $analysis['sell_analysis']);
        }

        return response()->json($analysis);
    }

    /**
     * Get price outliers for manual review
     */
    public function getOutliers(Request $request): JsonResponse
    {
        $asset = $request->get('asset', 'USDT');
        $fiat = $request->get('fiat', 'VES');
        $tradeType = strtoupper($request->get('trade_type', 'BUY'));
        $rows = min((int) $request->get('rows', 50), 100);
        $outlierMethod = $request->get('outlier_method', 'iqr');

        if (! in_array($tradeType, ['BUY', 'SELL'])) {
            return response()->json(['error' => 'Invalid trade_type. Must be BUY or SELL'], 400);
        }

        if (! in_array($outlierMethod, ['iqr', 'zscore', 'modified_zscore'])) {
            return response()->json(['error' => 'Invalid outlier_method. Must be iqr, zscore, or modified_zscore'], 400);
        }

        $data = $this->binanceP2PService->getP2PData($asset, $fiat, $tradeType, 1, $rows);

        if ($data === null) {
            return response()->json(['error' => 'Failed to fetch P2P data'], 500);
        }

        $priceData = $this->statisticalAnalysisService->analyzeMarketData($data, [
            'outlier_method' => $outlierMethod,
        ]);

        $outlierDetails = [];
        if (! empty($data['data']) && ! empty($priceData['outlier_analysis']['outlier_indices'])) {
            foreach ($priceData['outlier_analysis']['outlier_indices'] as $index) {
                if (isset($data['data'][$index])) {
                    $outlierDetails[] = [
                        'index' => $index,
                        'price' => (float) $data['data'][$index]['adv']['price'],
                        'volume' => (float) ($data['data'][$index]['adv']['tradableQuantity'] ?? 0),
                        'merchant' => $data['data'][$index]['advertiser']['nickName'] ?? 'Unknown',
                        'completion_rate' => (float) ($data['data'][$index]['advertiser']['monthFinishRate'] ?? 0),
                        'trade_count' => (int) ($data['data'][$index]['advertiser']['monthOrderCount'] ?? 0),
                    ];
                }
            }
        }

        return response()->json([
            'asset' => $asset,
            'fiat' => $fiat,
            'trade_type' => $tradeType,
            'outlier_method' => $outlierMethod,
            'outlier_summary' => $priceData['outlier_analysis'],
            'outlier_details' => $outlierDetails,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get volatility analysis for different time periods
     */
    public function getVolatilityAnalysis(Request $request): JsonResponse
    {
        $asset = $request->get('asset', 'USDT');
        $fiat = $request->get('fiat', 'VES');
        $tradeType = strtoupper($request->get('trade_type', 'BUY'));
        $rows = min((int) $request->get('rows', 100), 200); // Larger sample for volatility

        if (! in_array($tradeType, ['BUY', 'SELL'])) {
            return response()->json(['error' => 'Invalid trade_type. Must be BUY or SELL'], 400);
        }

        $data = $this->binanceP2PService->getP2PData($asset, $fiat, $tradeType, 1, $rows);

        if ($data === null) {
            return response()->json(['error' => 'Failed to fetch P2P data'], 500);
        }

        $analysis = $this->statisticalAnalysisService->analyzeMarketData($data, [
            'outlier_method' => 'iqr', // Use IQR for volatility analysis
        ]);

        return response()->json([
            'asset' => $asset,
            'fiat' => $fiat,
            'trade_type' => $tradeType,
            'timestamp' => now()->toISOString(),
            'volatility_analysis' => $analysis['volatility_analysis'],
            'statistical_tests' => $analysis['statistical_tests'],
            'quality_metrics' => $analysis['quality_metrics'],
            'sample_size' => $rows,
        ]);
    }

    /**
     * Compare different outlier detection methods
     */
    public function compareOutlierMethods(Request $request): JsonResponse
    {
        $asset = $request->get('asset', 'USDT');
        $fiat = $request->get('fiat', 'VES');
        $tradeType = strtoupper($request->get('trade_type', 'BUY'));
        $rows = min((int) $request->get('rows', 50), 100);

        if (! in_array($tradeType, ['BUY', 'SELL'])) {
            return response()->json(['error' => 'Invalid trade_type. Must be BUY or SELL'], 400);
        }

        $data = $this->binanceP2PService->getP2PData($asset, $fiat, $tradeType, 1, $rows);

        if ($data === null) {
            return response()->json(['error' => 'Failed to fetch P2P data'], 500);
        }

        $methods = ['iqr', 'zscore', 'modified_zscore'];
        $comparison = [
            'asset' => $asset,
            'fiat' => $fiat,
            'trade_type' => $tradeType,
            'timestamp' => now()->toISOString(),
            'methods' => [],
        ];

        foreach ($methods as $method) {
            $analysis = $this->statisticalAnalysisService->analyzeMarketData($data, [
                'outlier_method' => $method,
            ]);

            $comparison['methods'][$method] = [
                'outlier_analysis' => $analysis['outlier_analysis'],
                'cleaned_mean' => $analysis['cleaned_statistics']['mean'],
                'cleaned_std' => $analysis['cleaned_statistics']['standard_deviation'],
                'data_retention_rate' => $analysis['quality_metrics']['data_retention_rate'],
                'quality_score' => $analysis['quality_metrics']['quality_score'],
            ];
        }

        return response()->json($comparison);
    }

    /**
     * Compare buy and sell markets
     */
    private function compareMarkets(array $buyAnalysis, array $sellAnalysis): array
    {
        $buyMean = $buyAnalysis['cleaned_statistics']['mean'] ?? 0;
        $sellMean = $sellAnalysis['cleaned_statistics']['mean'] ?? 0;

        $spread = $sellMean - $buyMean;
        $spreadPercentage = $buyMean > 0 ? ($spread / $buyMean) * 100 : 0;

        return [
            'price_spread' => [
                'absolute' => round($spread, 8),
                'percentage' => round($spreadPercentage, 4),
                'assessment' => $spreadPercentage < 1 ? 'tight' : ($spreadPercentage < 3 ? 'normal' : 'wide'),
            ],
            'volatility_comparison' => [
                'buy_volatility' => $buyAnalysis['volatility_analysis']['relative_volatility'] ?? 0,
                'sell_volatility' => $sellAnalysis['volatility_analysis']['relative_volatility'] ?? 0,
                'volatility_difference' => abs(($buyAnalysis['volatility_analysis']['relative_volatility'] ?? 0) -
                                                ($sellAnalysis['volatility_analysis']['relative_volatility'] ?? 0)),
            ],
            'liquidity_comparison' => [
                'buy_sample_size' => $buyAnalysis['raw_statistics']['count'] ?? 0,
                'sell_sample_size' => $sellAnalysis['raw_statistics']['count'] ?? 0,
                'liquidity_balance' => abs(($buyAnalysis['raw_statistics']['count'] ?? 0) -
                                          ($sellAnalysis['raw_statistics']['count'] ?? 0)),
            ],
            'quality_comparison' => [
                'buy_quality_score' => $buyAnalysis['quality_metrics']['quality_score'] ?? 0,
                'sell_quality_score' => $sellAnalysis['quality_metrics']['quality_score'] ?? 0,
                'quality_difference' => abs(($buyAnalysis['quality_metrics']['quality_score'] ?? 0) -
                                           ($sellAnalysis['quality_metrics']['quality_score'] ?? 0)),
            ],
            'arbitrage_opportunity' => [
                'exists' => $spread > 0,
                'potential_profit_percentage' => max(0, $spreadPercentage),
                'risk_assessment' => $this->assessArbitrageRisk($spread, $spreadPercentage, $buyAnalysis, $sellAnalysis),
            ],
        ];
    }

    /**
     * Get historical price data from market snapshots
     *
     * Retrieves historical P2P market data from stored snapshots in the database.
     * This endpoint provides time-series data for building charts and analyzing price trends.
     *
     * @param  Request  $request
     *                            Query parameters:
     *                            - asset (string, optional): Cryptocurrency asset symbol (default: USDT)
     *                            - fiat (string, optional): Fiat currency symbol (default: VES)
     *                            - hours (int, optional): Number of hours to look back (default: 24, max: 168)
     * @return JsonResponse
     *                      Returns structured historical data including:
     *                      - historical_data: Array of timestamped price points with quality scores
     *                      - spread_data: Buy/sell spread analysis where both sides exist
     *                      - summary: Statistical overview including price volatility and data quality
     */
    public function getHistoricalPrices(Request $request): JsonResponse
    {
        $asset = strtoupper($request->get('asset', 'USDT'));
        $fiat = strtoupper($request->get('fiat', 'VES'));
        $hours = min((int) $request->get('hours', 24), 168); // Limit to 1 week maximum
        $intervalMinutes = max((int) $request->get('interval', 10), 5); // Default 10 minutes, minimum 5

        // Validate parameters
        if (empty($asset) || empty($fiat)) {
            return response()->json(['error' => 'Asset and fiat parameters are required'], 400);
        }

        if ($hours <= 0) {
            return response()->json(['error' => 'Hours parameter must be positive'], 400);
        }

        try {
            // Find the trading pair
            $tradingPair = \App\Models\TradingPair::where('asset', $asset)
                ->where('fiat', $fiat)
                ->where('is_active', true)
                ->first();

            if (! $tradingPair) {
                return response()->json([
                    'error' => "Trading pair {$asset}/{$fiat} not found or inactive",
                ], 404);
            }

            // Get historical snapshots from the last N hours
            $startTime = now()->subHours($hours);
            $snapshots = \App\Models\P2PMarketSnapshot::where('trading_pair_id', $tradingPair->id)
                ->where('collected_at', '>=', $startTime)
                ->where('data_quality_score', '>=', 0.5) // Only include good quality data
                ->orderBy('collected_at', 'asc')
                ->get();

            if ($snapshots->isEmpty()) {
                return response()->json([
                    'asset' => $asset,
                    'fiat' => $fiat,
                    'hours' => $hours,
                    'message' => 'No historical data available for the specified time period',
                    'historical_data' => [],
                ]);
            }

            // Group snapshots by time intervals (e.g., every 10 minutes)
            $groupedData = $this->groupSnapshotsByInterval($snapshots, $intervalMinutes);

            // Process grouped data and extract price data
            $historicalData = [];
            $buyPrices = [];
            $sellPrices = [];

            foreach ($groupedData as $intervalData) {
                $timestamp = $intervalData['timestamp'];
                $avgDataPoint = $intervalData['avg_data'];

                // Create data point for this interval
                $dataPoint = [
                    'timestamp' => $timestamp,
                    'collected_at_unix' => strtotime($timestamp),
                    'trade_type' => $avgDataPoint['trade_type'],
                    'best_price' => $avgDataPoint['best_price'],
                    'avg_price' => $avgDataPoint['avg_price'],
                    'worst_price' => $avgDataPoint['worst_price'],
                    'median_price' => $avgDataPoint['median_price'],
                    'volume_weighted_price' => $avgDataPoint['volume_weighted_price'],
                    'total_volume' => $avgDataPoint['total_volume'],
                    'order_count' => $avgDataPoint['order_count'],
                    'price_spread' => $avgDataPoint['price_spread'],
                    'data_quality_score' => $avgDataPoint['data_quality_score'],
                    'data_points_in_interval' => $intervalData['count'],
                ];

                $historicalData[] = $dataPoint;

                // Separate buy and sell prices for spread calculation
                if ($avgDataPoint['trade_type'] === 'BUY') {
                    $buyPrices[$timestamp] = $avgDataPoint['avg_price'];
                } elseif ($avgDataPoint['trade_type'] === 'SELL') {
                    $sellPrices[$timestamp] = $avgDataPoint['avg_price'];
                }
            }

            // Calculate spread data where both buy and sell prices exist
            $spreadData = [];
            foreach ($buyPrices as $timestamp => $buyPrice) {
                if (isset($sellPrices[$timestamp]) && $buyPrice && $sellPrices[$timestamp]) {
                    $spread = $sellPrices[$timestamp] - $buyPrice;
                    $spreadPercentage = ($spread / $buyPrice) * 100;

                    $spreadData[] = [
                        'timestamp' => $timestamp,
                        'buy_price' => $buyPrice,
                        'sell_price' => $sellPrices[$timestamp],
                        'spread_absolute' => round($spread, 8),
                        'spread_percentage' => round($spreadPercentage, 4),
                    ];
                }
            }

            // Calculate summary statistics
            $allPrices = collect($historicalData)->pluck('avg_price')->filter()->values();
            $summary = [
                'total_data_points' => count($historicalData),
                'unique_timestamps' => count(array_unique(array_column($historicalData, 'timestamp'))),
                'time_range' => [
                    'start' => $snapshots->first()->collected_at->toISOString(),
                    'end' => $snapshots->last()->collected_at->toISOString(),
                    'duration_hours' => $snapshots->first()->collected_at->diffInHours($snapshots->last()->collected_at),
                ],
                'price_summary' => $allPrices->isNotEmpty() ? [
                    'min_price' => $allPrices->min(),
                    'max_price' => $allPrices->max(),
                    'avg_price' => round($allPrices->avg(), 8),
                    'price_volatility' => $allPrices->count() > 1 ? $this->calculateVolatility($allPrices) : 0,
                ] : null,
                'data_quality' => [
                    'avg_quality_score' => round($snapshots->avg('data_quality_score'), 4),
                    'min_quality_score' => $snapshots->min('data_quality_score'),
                    'max_quality_score' => $snapshots->max('data_quality_score'),
                ],
                'spread_opportunities' => count($spreadData),
            ];

            return response()->json([
                'asset' => $asset,
                'fiat' => $fiat,
                'hours' => $hours,
                'summary' => $summary,
                'historical_data' => $historicalData,
                'spread_data' => $spreadData,
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve historical price data',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Calculate price volatility (coefficient of variation) for a collection of prices
     */
    private function calculateVolatility(\Illuminate\Support\Collection $prices): float
    {
        if ($prices->count() <= 1) {
            return 0;
        }

        $mean = $prices->avg();
        if ($mean == 0) {
            return 0;
        }

        // Calculate standard deviation manually
        $variance = $prices->map(function ($price) use ($mean) {
            return pow($price - $mean, 2);
        })->avg();

        $standardDeviation = sqrt($variance);

        // Return coefficient of variation as percentage
        return round(($standardDeviation / $mean) * 100, 4);
    }

    /**
     * Group snapshots by time intervals for better chart performance
     */
    private function groupSnapshotsByInterval($snapshots, int $intervalMinutes): array
    {
        $grouped = [];

        foreach ($snapshots as $snapshot) {
            $priceStats = $snapshot->getPriceStatistics();

            // Round timestamp to nearest interval
            $timestamp = $snapshot->collected_at;
            $roundedMinutes = floor($timestamp->minute / $intervalMinutes) * $intervalMinutes;
            $intervalKey = $timestamp->startOfHour()->addMinutes($roundedMinutes)->toISOString();

            $key = $intervalKey.'_'.$snapshot->trade_type;

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'timestamp' => $intervalKey,
                    'trade_type' => $snapshot->trade_type,
                    'snapshots' => [],
                    'price_stats' => [],
                ];
            }

            $grouped[$key]['snapshots'][] = $snapshot;
            $grouped[$key]['price_stats'][] = $priceStats;
        }

        // Calculate averages for each interval
        $result = [];
        foreach ($grouped as $key => $group) {
            $stats = $group['price_stats'];
            $snapshots = $group['snapshots'];

            if (empty($stats)) {
                continue;
            }

            $avgData = [
                'trade_type' => $group['trade_type'],
                'best_price' => collect($stats)->avg('best_price'),
                'avg_price' => collect($stats)->avg('avg_price'),
                'worst_price' => collect($stats)->avg('worst_price'),
                'median_price' => collect($stats)->avg('median_price'),
                'volume_weighted_price' => collect($stats)->avg('volume_weighted_price'),
                'total_volume' => collect($stats)->sum('total_volume'),
                'order_count' => collect($stats)->sum('order_count'),
                'price_spread' => collect($stats)->avg('price_spread'),
                'data_quality_score' => collect($snapshots)->avg('data_quality_score'),
            ];

            $result[] = [
                'timestamp' => $group['timestamp'],
                'avg_data' => $avgData,
                'count' => count($snapshots),
            ];
        }

        // Sort by timestamp
        usort($result, function ($a, $b) {
            return strtotime($a['timestamp']) - strtotime($b['timestamp']);
        });

        return $result;
    }

    /**
     * Get available trading pairs from the database
     */
    public function getAvailableTradingPairs(): JsonResponse
    {
        try {
            $tradingPairs = \App\Models\TradingPair::where('is_active', true)
                ->select('asset', 'fiat', 'pair_symbol')
                ->orderBy('asset')
                ->orderBy('fiat')
                ->get();

            // Group by assets and fiats for easier filtering on frontend
            $assets = $tradingPairs->pluck('asset')->unique()->sort()->values();
            $fiats = $tradingPairs->pluck('fiat')->unique()->sort()->values();

            // Create mapping for filtering
            $assetFiatMapping = [];
            $fiatAssetMapping = [];

            foreach ($tradingPairs as $pair) {
                if (! isset($assetFiatMapping[$pair->asset])) {
                    $assetFiatMapping[$pair->asset] = [];
                }
                $assetFiatMapping[$pair->asset][] = $pair->fiat;

                if (! isset($fiatAssetMapping[$pair->fiat])) {
                    $fiatAssetMapping[$pair->fiat] = [];
                }
                $fiatAssetMapping[$pair->fiat][] = $pair->asset;
            }

            return response()->json([
                'assets' => $assets,
                'fiats' => $fiats,
                'pairs' => $tradingPairs,
                'asset_fiat_mapping' => $assetFiatMapping,
                'fiat_asset_mapping' => $fiatAssetMapping,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve trading pairs',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Assess arbitrage risk based on market conditions
     */
    private function assessArbitrageRisk(float $spread, float $spreadPercentage, array $buyAnalysis, array $sellAnalysis): string
    {
        $buyVolatility = $buyAnalysis['volatility_analysis']['relative_volatility'] ?? 0;
        $sellVolatility = $sellAnalysis['volatility_analysis']['relative_volatility'] ?? 0;
        $avgVolatility = ($buyVolatility + $sellVolatility) / 2;

        $buyQuality = $buyAnalysis['quality_metrics']['quality_score'] ?? 0;
        $sellQuality = $sellAnalysis['quality_metrics']['quality_score'] ?? 0;
        $avgQuality = ($buyQuality + $sellQuality) / 2;

        if ($spreadPercentage < 0.5) {
            return 'very_low';
        }
        if ($spreadPercentage < 1.0 && $avgVolatility < 10 && $avgQuality > 0.8) {
            return 'low';
        }
        if ($spreadPercentage < 2.0 && $avgVolatility < 20 && $avgQuality > 0.6) {
            return 'moderate';
        }
        if ($spreadPercentage < 5.0 && $avgVolatility < 50) {
            return 'high';
        }

        return 'very_high';
    }
}
