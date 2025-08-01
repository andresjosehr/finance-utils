<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class MarketStatistics extends Model
{
    use HasFactory;

    protected $fillable = [
        'trading_pair_id',
        'timeframe',
        'period_start',
        'period_end',
        'buy_open_price',
        'buy_close_price',
        'buy_high_price',
        'buy_low_price',
        'buy_avg_price',
        'buy_vwap',
        'buy_volatility',
        'sell_open_price',
        'sell_close_price',
        'sell_high_price',
        'sell_low_price',
        'sell_avg_price',
        'sell_vwap',
        'sell_volatility',
        'buy_total_volume',
        'sell_total_volume',
        'buy_avg_volume',
        'sell_avg_volume',
        'buy_order_count',
        'sell_order_count',
        'avg_bid_ask_spread',
        'liquidity_ratio',
        'market_efficiency',
        'order_book_depth',
        'unique_merchants',
        'pro_merchant_ratio',
        'avg_merchant_completion_rate',
        'avg_payment_time',
        'price_momentum',
        'volume_trend',
        'market_sentiment',
        'data_points_count',
        'data_coverage',
        'avg_data_quality',
        'has_price_anomaly',
        'has_volume_anomaly',
        'anomaly_details',
    ];

    protected $casts = [
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'buy_open_price' => 'decimal:8',
        'buy_close_price' => 'decimal:8',
        'buy_high_price' => 'decimal:8',
        'buy_low_price' => 'decimal:8',
        'buy_avg_price' => 'decimal:8',
        'buy_vwap' => 'decimal:8',
        'buy_volatility' => 'decimal:4',
        'sell_open_price' => 'decimal:8',
        'sell_close_price' => 'decimal:8',
        'sell_high_price' => 'decimal:8',
        'sell_low_price' => 'decimal:8',
        'sell_avg_price' => 'decimal:8',
        'sell_vwap' => 'decimal:8',
        'sell_volatility' => 'decimal:4',
        'buy_total_volume' => 'decimal:8',
        'sell_total_volume' => 'decimal:8',
        'buy_avg_volume' => 'decimal:8',
        'sell_avg_volume' => 'decimal:8',
        'buy_order_count' => 'integer',
        'sell_order_count' => 'integer',
        'avg_bid_ask_spread' => 'decimal:8',
        'liquidity_ratio' => 'decimal:4',
        'market_efficiency' => 'decimal:4',
        'order_book_depth' => 'decimal:8',
        'unique_merchants' => 'integer',
        'pro_merchant_ratio' => 'integer',
        'avg_merchant_completion_rate' => 'decimal:4',
        'avg_payment_time' => 'decimal:2',
        'price_momentum' => 'decimal:4',
        'volume_trend' => 'decimal:4',
        'data_points_count' => 'integer',
        'data_coverage' => 'decimal:4',
        'avg_data_quality' => 'decimal:4',
        'has_price_anomaly' => 'boolean',
        'has_volume_anomaly' => 'boolean',
        'anomaly_details' => 'array',
    ];

    /**
     * Get the trading pair that owns this market statistics entry
     */
    public function tradingPair(): BelongsTo
    {
        return $this->belongsTo(TradingPair::class);
    }

    /**
     * Scope to filter by trading pair
     */
    public function scopeForTradingPair(Builder $query, int $tradingPairId): Builder
    {
        return $query->where('trading_pair_id', $tradingPairId);
    }

    /**
     * Scope to filter by timeframe
     */
    public function scopeForTimeframe(Builder $query, string $timeframe): Builder
    {
        return $query->where('timeframe', $timeframe);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeBetweenDates(Builder $query, Carbon $startDate, Carbon $endDate): Builder
    {
        return $query->where('period_start', '>=', $startDate)
            ->where('period_end', '<=', $endDate);
    }

    /**
     * Scope to filter by market sentiment
     */
    public function scopeWithSentiment(Builder $query, string $sentiment): Builder
    {
        return $query->where('market_sentiment', $sentiment);
    }

    /**
     * Scope to filter entries with anomalies
     */
    public function scopeWithAnomalies(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('has_price_anomaly', true)
                ->orWhere('has_volume_anomaly', true);
        });
    }

    /**
     * Scope to get recent statistics
     */
    public function scopeRecent(Builder $query, int $hours = 24): Builder
    {
        return $query->where('period_start', '>=', Carbon::now()->subHours($hours));
    }

    /**
     * Generate market statistics for a specific period
     */
    public static function generateStatistics(int $tradingPairId, string $timeframe, Carbon $periodStart, Carbon $periodEnd): ?self
    {
        // Get price history data for the period
        $priceHistory = PriceHistory::forTradingPair($tradingPairId)
            ->whereBetween('recorded_at', [$periodStart, $periodEnd])
            ->orderBy('recorded_at', 'asc')
            ->get();

        if ($priceHistory->isEmpty()) {
            return null;
        }

        $buyData = $priceHistory->where('trade_type', 'BUY');
        $sellData = $priceHistory->where('trade_type', 'SELL');

        $statistics = new self([
            'trading_pair_id' => $tradingPairId,
            'timeframe' => $timeframe,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'data_points_count' => $priceHistory->count(),
        ]);

        // Calculate buy statistics
        if ($buyData->isNotEmpty()) {
            $statistics->fill([
                'buy_open_price' => $buyData->first()->best_price,
                'buy_close_price' => $buyData->last()->best_price,
                'buy_high_price' => $buyData->max('best_price'),
                'buy_low_price' => $buyData->min('best_price'),
                'buy_avg_price' => $buyData->avg('best_price'),
                'buy_vwap' => $statistics->calculateVWAP($buyData),
                'buy_volatility' => $statistics->calculateVolatility($buyData),
                'buy_total_volume' => $buyData->sum('total_volume'),
                'buy_avg_volume' => $buyData->avg('total_volume'),
                'buy_order_count' => $buyData->sum('active_orders'),
            ]);
        }

        // Calculate sell statistics
        if ($sellData->isNotEmpty()) {
            $statistics->fill([
                'sell_open_price' => $sellData->first()->best_price,
                'sell_close_price' => $sellData->last()->best_price,
                'sell_high_price' => $sellData->max('best_price'),
                'sell_low_price' => $sellData->min('best_price'),
                'sell_avg_price' => $sellData->avg('best_price'),
                'sell_vwap' => $statistics->calculateVWAP($sellData),
                'sell_volatility' => $statistics->calculateVolatility($sellData),
                'sell_total_volume' => $sellData->sum('total_volume'),
                'sell_avg_volume' => $sellData->avg('total_volume'),
                'sell_order_count' => $sellData->sum('active_orders'),
            ]);
        }

        // Calculate market-wide statistics
        $statistics->calculateMarketMetrics($priceHistory, $buyData, $sellData);

        // Detect anomalies
        $statistics->detectAnomalies($priceHistory);

        $statistics->save();

        return $statistics;
    }

    /**
     * Calculate market-wide metrics
     */
    private function calculateMarketMetrics(Collection $allData, Collection $buyData, Collection $sellData): void
    {
        // Calculate bid-ask spread
        if ($buyData->isNotEmpty() && $sellData->isNotEmpty()) {
            $avgBuyPrice = $buyData->avg('best_price');
            $avgSellPrice = $sellData->avg('best_price');
            $this->avg_bid_ask_spread = abs($avgSellPrice - $avgBuyPrice);
        }

        // Calculate liquidity metrics
        $this->liquidity_ratio = $this->calculateLiquidityRatio($allData);
        $this->market_efficiency = $this->calculateMarketEfficiency($allData);
        $this->order_book_depth = $allData->sum('total_volume');

        // Calculate merchant statistics
        $this->unique_merchants = $allData->sum('merchant_count');
        $this->pro_merchant_ratio = $this->calculateProMerchantRatio($allData);
        $this->avg_merchant_completion_rate = $allData->avg('avg_completion_rate');
        $this->avg_payment_time = $allData->avg('avg_pay_time');

        // Calculate trends
        $this->price_momentum = $this->calculatePriceMomentum($allData);
        $this->volume_trend = $this->calculateVolumeTrend($allData);
        $this->market_sentiment = $this->determineMarketSentiment();

        // Calculate data quality metrics
        $this->data_coverage = $this->calculateDataCoverage();
        $this->avg_data_quality = $allData->avg('data_quality_score');
    }

    /**
     * Calculate Volume Weighted Average Price (VWAP)
     */
    private function calculateVWAP(Collection $data): float
    {
        $totalValue = 0;
        $totalVolume = 0;

        foreach ($data as $entry) {
            $value = $entry->best_price * $entry->total_volume;
            $totalValue += $value;
            $totalVolume += $entry->total_volume;
        }

        return $totalVolume > 0 ? $totalValue / $totalVolume : 0;
    }

    /**
     * Calculate price volatility (standard deviation)
     */
    private function calculateVolatility(Collection $data): float
    {
        if ($data->count() < 2) {
            return 0;
        }

        $prices = $data->pluck('best_price')->toArray();
        $returns = [];

        for ($i = 1; $i < count($prices); $i++) {
            $prevPrice = $prices[$i - 1];
            $currentPrice = $prices[$i];
            $return = $prevPrice > 0 ? (($currentPrice - $prevPrice) / $prevPrice) : 0;
            $returns[] = $return;
        }

        if (empty($returns)) {
            return 0;
        }

        $mean = array_sum($returns) / count($returns);
        $variance = array_sum(array_map(function ($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $returns)) / (count($returns) - 1);

        return sqrt($variance) * 100; // Convert to percentage
    }

    /**
     * Calculate liquidity ratio
     */
    private function calculateLiquidityRatio(Collection $data): float
    {
        if ($data->isEmpty()) {
            return 0;
        }

        $avgLiquidityScore = $data->whereNotNull('liquidity_score')->avg('liquidity_score');

        return $avgLiquidityScore ?? 0;
    }

    /**
     * Calculate market efficiency
     */
    private function calculateMarketEfficiency(Collection $data): float
    {
        if ($data->count() < 2) {
            return 1;
        }

        // Market efficiency based on price stability and spread consistency
        $spreads = $data->whereNotNull('price_spread_percentage')->pluck('price_spread_percentage')->toArray();

        if (empty($spreads)) {
            return 1;
        }

        $avgSpread = array_sum($spreads) / count($spreads);
        $spreadStdDev = $this->calculateStandardDeviation($spreads);

        // Lower spread variation = higher efficiency
        $efficiency = $avgSpread > 0 ? max(0, 1 - ($spreadStdDev / $avgSpread)) : 1;

        return min(1, $efficiency);
    }

    /**
     * Calculate pro merchant ratio
     */
    private function calculateProMerchantRatio(Collection $data): int
    {
        $totalMerchants = $data->sum('merchant_count');
        $proMerchants = $data->sum('pro_merchant_count');

        return $totalMerchants > 0 ? round(($proMerchants / $totalMerchants) * 100) : 0;
    }

    /**
     * Calculate price momentum
     */
    private function calculatePriceMomentum(Collection $data): float
    {
        if ($data->count() < 2) {
            return 0;
        }

        $firstPrice = $data->first()->best_price;
        $lastPrice = $data->last()->best_price;

        return $firstPrice > 0 ? (($lastPrice - $firstPrice) / $firstPrice) * 100 : 0;
    }

    /**
     * Calculate volume trend
     */
    private function calculateVolumeTrend(Collection $data): float
    {
        if ($data->count() < 2) {
            return 0;
        }

        $firstVolume = $data->first()->total_volume;
        $lastVolume = $data->last()->total_volume;

        return $firstVolume > 0 ? (($lastVolume - $firstVolume) / $firstVolume) * 100 : 0;
    }

    /**
     * Determine market sentiment based on price and volume trends
     */
    private function determineMarketSentiment(): string
    {
        $priceTrend = $this->price_momentum ?? 0;
        $volumeTrend = $this->volume_trend ?? 0;

        // Strong bullish: rising prices with increasing volume
        if ($priceTrend > 2 && $volumeTrend > 0) {
            return 'bullish';
        }

        // Strong bearish: falling prices with increasing volume
        if ($priceTrend < -2 && $volumeTrend > 0) {
            return 'bearish';
        }

        // Weak signals or conflicting indicators
        if (abs($priceTrend) <= 2) {
            return 'neutral';
        }

        // Price trend without volume confirmation
        return $priceTrend > 0 ? 'bullish' : 'bearish';
    }

    /**
     * Calculate data coverage for the period
     */
    private function calculateDataCoverage(): float
    {
        $expectedPoints = $this->getExpectedDataPoints();
        $actualPoints = $this->data_points_count;

        return $expectedPoints > 0 ? min(1, $actualPoints / $expectedPoints) : 1;
    }

    /**
     * Get expected number of data points for the timeframe
     */
    private function getExpectedDataPoints(): int
    {
        $duration = $this->period_start->diffInMinutes($this->period_end);

        return match ($this->timeframe) {
            '5m' => 1, // One data point per 5-minute period
            '15m' => 3, // Expect 3 data points in 15 minutes
            '1h' => 12, // Expect 12 data points in 1 hour
            '4h' => 48, // Expect 48 data points in 4 hours
            '1d' => 288, // Expect 288 data points in 1 day (5-min intervals)
            '1w' => 2016, // Expect 2016 data points in 1 week
            default => max(1, intval($duration / 5)), // Default: 5-minute intervals
        };
    }

    /**
     * Detect price and volume anomalies
     */
    private function detectAnomalies(Collection $data): void
    {
        $anomalies = [];

        // Detect price anomalies (using z-score)
        $priceAnomalies = $this->detectPriceAnomalies($data);
        if (! empty($priceAnomalies)) {
            $this->has_price_anomaly = true;
            $anomalies['price'] = $priceAnomalies;
        }

        // Detect volume anomalies
        $volumeAnomalies = $this->detectVolumeAnomalies($data);
        if (! empty($volumeAnomalies)) {
            $this->has_volume_anomaly = true;
            $anomalies['volume'] = $volumeAnomalies;
        }

        if (! empty($anomalies)) {
            $this->anomaly_details = $anomalies;
        }
    }

    /**
     * Detect price anomalies using z-score
     */
    private function detectPriceAnomalies(Collection $data, float $threshold = 3.0): array
    {
        $prices = $data->pluck('best_price')->toArray();

        if (count($prices) < 3) {
            return [];
        }

        $mean = array_sum($prices) / count($prices);
        $stdDev = $this->calculateStandardDeviation($prices);

        if ($stdDev == 0) {
            return [];
        }

        $anomalies = [];
        foreach ($prices as $index => $price) {
            $zScore = abs(($price - $mean) / $stdDev);
            if ($zScore > $threshold) {
                $anomalies[] = [
                    'index' => $index,
                    'price' => $price,
                    'z_score' => round($zScore, 2),
                    'deviation_percentage' => round((($price - $mean) / $mean) * 100, 2),
                ];
            }
        }

        return $anomalies;
    }

    /**
     * Detect volume anomalies
     */
    private function detectVolumeAnomalies(Collection $data, float $threshold = 3.0): array
    {
        $volumes = $data->pluck('total_volume')->toArray();

        if (count($volumes) < 3) {
            return [];
        }

        $mean = array_sum($volumes) / count($volumes);
        $stdDev = $this->calculateStandardDeviation($volumes);

        if ($stdDev == 0) {
            return [];
        }

        $anomalies = [];
        foreach ($volumes as $index => $volume) {
            $zScore = abs(($volume - $mean) / $stdDev);
            if ($zScore > $threshold) {
                $anomalies[] = [
                    'index' => $index,
                    'volume' => $volume,
                    'z_score' => round($zScore, 2),
                    'deviation_percentage' => round((($volume - $mean) / $mean) * 100, 2),
                ];
            }
        }

        return $anomalies;
    }

    /**
     * Calculate standard deviation
     */
    private function calculateStandardDeviation(array $values): float
    {
        if (count($values) < 2) {
            return 0;
        }

        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(function ($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $values)) / (count($values) - 1);

        return sqrt($variance);
    }

    /**
     * Get comparative analysis with previous period
     */
    public function getComparativeAnalysis(): array
    {
        $previousPeriod = self::forTradingPair($this->trading_pair_id)
            ->forTimeframe($this->timeframe)
            ->where('period_end', '<', $this->period_start)
            ->orderBy('period_end', 'desc')
            ->first();

        if (! $previousPeriod) {
            return [
                'has_comparison' => false,
                'message' => 'No previous period data available for comparison',
            ];
        }

        $comparison = [
            'has_comparison' => true,
            'previous_period' => [
                'start' => $previousPeriod->period_start->toISOString(),
                'end' => $previousPeriod->period_end->toISOString(),
            ],
            'price_changes' => [
                'buy' => [
                    'open' => $this->calculatePercentageChange($previousPeriod->buy_open_price, $this->buy_open_price),
                    'close' => $this->calculatePercentageChange($previousPeriod->buy_close_price, $this->buy_close_price),
                    'high' => $this->calculatePercentageChange($previousPeriod->buy_high_price, $this->buy_high_price),
                    'low' => $this->calculatePercentageChange($previousPeriod->buy_low_price, $this->buy_low_price),
                ],
                'sell' => [
                    'open' => $this->calculatePercentageChange($previousPeriod->sell_open_price, $this->sell_open_price),
                    'close' => $this->calculatePercentageChange($previousPeriod->sell_close_price, $this->sell_close_price),
                    'high' => $this->calculatePercentageChange($previousPeriod->sell_high_price, $this->sell_high_price),
                    'low' => $this->calculatePercentageChange($previousPeriod->sell_low_price, $this->sell_low_price),
                ],
            ],
            'volume_changes' => [
                'buy_total' => $this->calculatePercentageChange($previousPeriod->buy_total_volume, $this->buy_total_volume),
                'sell_total' => $this->calculatePercentageChange($previousPeriod->sell_total_volume, $this->sell_total_volume),
            ],
            'market_changes' => [
                'liquidity_ratio' => $this->calculatePercentageChange($previousPeriod->liquidity_ratio, $this->liquidity_ratio),
                'market_efficiency' => $this->calculatePercentageChange($previousPeriod->market_efficiency, $this->market_efficiency),
                'spread' => $this->calculatePercentageChange($previousPeriod->avg_bid_ask_spread, $this->avg_bid_ask_spread),
            ],
        ];

        return $comparison;
    }

    /**
     * Calculate percentage change between two values
     */
    private function calculatePercentageChange(?float $oldValue, ?float $newValue): ?float
    {
        if ($oldValue === null || $newValue === null || $oldValue == 0) {
            return null;
        }

        return round((($newValue - $oldValue) / $oldValue) * 100, 2);
    }

    /**
     * Get market summary for dashboard display
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'trading_pair_id' => $this->trading_pair_id,
            'timeframe' => $this->timeframe,
            'period' => [
                'start' => $this->period_start->toISOString(),
                'end' => $this->period_end->toISOString(),
            ],
            'buy_prices' => [
                'open' => $this->buy_open_price,
                'close' => $this->buy_close_price,
                'high' => $this->buy_high_price,
                'low' => $this->buy_low_price,
                'average' => $this->buy_avg_price,
                'vwap' => $this->buy_vwap,
            ],
            'sell_prices' => [
                'open' => $this->sell_open_price,
                'close' => $this->sell_close_price,
                'high' => $this->sell_high_price,
                'low' => $this->sell_low_price,
                'average' => $this->sell_avg_price,
                'vwap' => $this->sell_vwap,
            ],
            'volume' => [
                'buy_total' => $this->buy_total_volume,
                'sell_total' => $this->sell_total_volume,
                'total' => $this->buy_total_volume + $this->sell_total_volume,
            ],
            'market_metrics' => [
                'spread' => $this->avg_bid_ask_spread,
                'liquidity_ratio' => $this->liquidity_ratio,
                'market_efficiency' => $this->market_efficiency,
                'sentiment' => $this->market_sentiment,
            ],
            'trends' => [
                'price_momentum' => $this->price_momentum,
                'volume_trend' => $this->volume_trend,
                'volatility' => [
                    'buy' => $this->buy_volatility,
                    'sell' => $this->sell_volatility,
                ],
            ],
            'quality' => [
                'data_points' => $this->data_points_count,
                'data_coverage' => $this->data_coverage,
                'avg_quality' => $this->avg_data_quality,
                'has_anomalies' => $this->has_price_anomaly || $this->has_volume_anomaly,
            ],
        ];
    }
}
