<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PriceHistory extends Model
{
    use HasFactory;

    protected $table = 'price_history';

    protected $fillable = [
        'trading_pair_id',
        'recorded_at',
        'trade_type',
        'best_price',
        'avg_price',
        'worst_price',
        'median_price',
        'total_volume',
        'total_fiat_volume',
        'active_orders',
        'merchant_count',
        'pro_merchant_count',
        'price_spread',
        'price_spread_percentage',
        'volume_concentration',
        'liquidity_score',
        'avg_completion_rate',
        'avg_pay_time',
        'data_quality_score',
        'price_percentiles',
        'volume_distribution',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'best_price' => 'decimal:8',
        'avg_price' => 'decimal:8',
        'worst_price' => 'decimal:8',
        'median_price' => 'decimal:8',
        'total_volume' => 'decimal:8',
        'total_fiat_volume' => 'decimal:8',
        'active_orders' => 'integer',
        'merchant_count' => 'integer',
        'pro_merchant_count' => 'integer',
        'price_spread' => 'decimal:8',
        'price_spread_percentage' => 'decimal:4',
        'volume_concentration' => 'decimal:4',
        'liquidity_score' => 'decimal:4',
        'avg_completion_rate' => 'decimal:4',
        'avg_pay_time' => 'decimal:2',
        'data_quality_score' => 'decimal:4',
        'price_percentiles' => 'array',
        'volume_distribution' => 'array',
    ];

    /**
     * Get the trading pair that owns this price history entry
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
     * Scope to filter by trade type
     */
    public function scopeForTradeType(Builder $query, string $tradeType): Builder
    {
        return $query->where('trade_type', strtoupper($tradeType));
    }

    /**
     * Scope to filter by date range
     */
    public function scopeBetweenDates(Builder $query, Carbon $startDate, Carbon $endDate): Builder
    {
        return $query->whereBetween('recorded_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get recent entries
     */
    public function scopeRecent(Builder $query, int $hours = 24): Builder
    {
        return $query->where('recorded_at', '>=', Carbon::now()->subHours($hours));
    }

    /**
     * Scope to filter by data quality
     */
    public function scopeHighQuality(Builder $query, float $threshold = 0.8): Builder
    {
        return $query->where('data_quality_score', '>=', $threshold);
    }

    /**
     * Scope to order by time (most recent first)
     */
    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderBy('recorded_at', 'desc');
    }

    /**
     * Scope to order by time (oldest first)
     */
    public function scopeOldest(Builder $query): Builder
    {
        return $query->orderBy('recorded_at', 'asc');
    }

    /**
     * Get price trend analysis for a specific period
     */
    public static function getPriceTrendAnalysis(int $tradingPairId, string $tradeType, int $hours = 24): array
    {
        $startTime = Carbon::now()->subHours($hours);
        
        $priceHistory = self::forTradingPair($tradingPairId)
            ->forTradeType($tradeType)
            ->where('recorded_at', '>=', $startTime)
            ->orderBy('recorded_at', 'asc')
            ->get(['recorded_at', 'best_price', 'avg_price', 'total_volume', 'price_spread_percentage']);

        if ($priceHistory->isEmpty()) {
            return [
                'trend' => 'unknown',
                'price_change' => 0,
                'price_change_percentage' => 0,
                'volatility' => 0,
                'volume_trend' => 0,
                'data_points' => 0,
            ];
        }

        $firstPrice = $priceHistory->first()->best_price;
        $lastPrice = $priceHistory->last()->best_price;
        $priceChange = $lastPrice - $firstPrice;
        $priceChangePercentage = $firstPrice > 0 ? ($priceChange / $firstPrice) * 100 : 0;

        // Calculate volatility (standard deviation of price changes)
        $priceChanges = [];
        for ($i = 1; $i < $priceHistory->count(); $i++) {
            $prevPrice = $priceHistory[$i - 1]->best_price;
            $currentPrice = $priceHistory[$i]->best_price;
            $change = $prevPrice > 0 ? (($currentPrice - $prevPrice) / $prevPrice) * 100 : 0;
            $priceChanges[] = $change;
        }

        $volatility = count($priceChanges) > 1 ? $this->calculateStandardDeviation($priceChanges) : 0;

        // Calculate volume trend
        $firstVolume = $priceHistory->first()->total_volume;
        $lastVolume = $priceHistory->last()->total_volume;
        $volumeTrend = $firstVolume > 0 ? (($lastVolume - $firstVolume) / $firstVolume) * 100 : 0;

        // Determine overall trend
        $trend = 'stable';
        if ($priceChangePercentage > 1) {
            $trend = 'bullish';
        } elseif ($priceChangePercentage < -1) {
            $trend = 'bearish';
        }

        return [
            'trend' => $trend,
            'price_change' => round($priceChange, 2),
            'price_change_percentage' => round($priceChangePercentage, 2),
            'volatility' => round($volatility, 2),
            'volume_trend' => round($volumeTrend, 2),
            'data_points' => $priceHistory->count(),
            'first_price' => $firstPrice,
            'last_price' => $lastPrice,
            'min_price' => $priceHistory->min('best_price'),
            'max_price' => $priceHistory->max('best_price'),
            'avg_spread' => round($priceHistory->avg('price_spread_percentage'), 2),
        ];
    }

    /**
     * Get OHLCV data for charting (Open, High, Low, Close, Volume)
     */
    public static function getOHLCVData(int $tradingPairId, string $tradeType, int $hours = 24, int $intervalMinutes = 60): array
    {
        $startTime = Carbon::now()->subHours($hours);
        
        $query = self::forTradingPair($tradingPairId)
            ->forTradeType($tradeType)
            ->where('recorded_at', '>=', $startTime)
            ->orderBy('recorded_at', 'asc');

        $priceHistory = $query->get();

        if ($priceHistory->isEmpty()) {
            return [];
        }

        // Group data by intervals
        $intervals = [];
        $currentInterval = null;
        $intervalData = [];

        foreach ($priceHistory as $entry) {
            $intervalTimestamp = $entry->recorded_at->floorMinutes($intervalMinutes);
            
            if ($currentInterval !== $intervalTimestamp->timestamp) {
                // Save previous interval if exists
                if (!empty($intervalData)) {
                    $intervals[] = $this->calculateOHLCVForInterval($currentInterval, $intervalData);
                }
                
                // Start new interval
                $currentInterval = $intervalTimestamp->timestamp;
                $intervalData = [];
            }
            
            $intervalData[] = $entry;
        }

        // Don't forget the last interval
        if (!empty($intervalData)) {
            $intervals[] = $this->calculateOHLCVForInterval($currentInterval, $intervalData);
        }

        return $intervals;
    }

    /**
     * Calculate support and resistance levels
     */
    public static function getSupportResistanceLevels(int $tradingPairId, string $tradeType, int $days = 7): array
    {
        $startTime = Carbon::now()->subDays($days);
        
        $priceHistory = self::forTradingPair($tradingPairId)
            ->forTradeType($tradeType)
            ->where('recorded_at', '>=', $startTime)
            ->orderBy('recorded_at', 'asc')
            ->get(['best_price', 'worst_price', 'total_volume']);

        if ($priceHistory->isEmpty()) {
            return [
                'support_levels' => [],
                'resistance_levels' => [],
                'current_price_context' => 'unknown',
            ];
        }

        $prices = $priceHistory->pluck('best_price')->toArray();
        $volumes = $priceHistory->pluck('total_volume')->toArray();

        // Find local minima (support) and maxima (resistance)
        $supportLevels = [];
        $resistanceLevels = [];
        $windowSize = max(3, min(10, count($prices) / 10));

        for ($i = $windowSize; $i < count($prices) - $windowSize; $i++) {
            $isLocalMin = true;
            $isLocalMax = true;
            
            // Check if current price is local minimum or maximum
            for ($j = $i - $windowSize; $j <= $i + $windowSize; $j++) {
                if ($j !== $i) {
                    if ($prices[$j] <= $prices[$i]) {
                        $isLocalMin = false;
                    }
                    if ($prices[$j] >= $prices[$i]) {
                        $isLocalMax = false;
                    }
                }
            }
            
            if ($isLocalMin) {
                $supportLevels[] = [
                    'price' => $prices[$i],
                    'volume' => $volumes[$i],
                    'strength' => $this->calculateLevelStrength($prices, $prices[$i], 0.5),
                ];
            }
            
            if ($isLocalMax) {
                $resistanceLevels[] = [
                    'price' => $prices[$i],
                    'volume' => $volumes[$i],
                    'strength' => $this->calculateLevelStrength($prices, $prices[$i], 0.5),
                ];
            }
        }

        // Sort by strength and take top levels
        usort($supportLevels, function ($a, $b) {
            return $b['strength'] <=> $a['strength'];
        });
        
        usort($resistanceLevels, function ($a, $b) {
            return $b['strength'] <=> $a['strength'];
        });

        $currentPrice = $priceHistory->last()->best_price;
        $nearestSupport = null;
        $nearestResistance = null;

        // Find nearest support (below current price)
        foreach ($supportLevels as $level) {
            if ($level['price'] < $currentPrice) {
                $nearestSupport = $level;
                break;
            }
        }

        // Find nearest resistance (above current price)
        foreach ($resistanceLevels as $level) {
            if ($level['price'] > $currentPrice) {
                $nearestResistance = $level;
                break;
            }
        }

        return [
            'support_levels' => array_slice($supportLevels, 0, 5),
            'resistance_levels' => array_slice($resistanceLevels, 0, 5),
            'nearest_support' => $nearestSupport,
            'nearest_resistance' => $nearestResistance,
            'current_price' => $currentPrice,
        ];
    }

    /**
     * Calculate price correlation between two trading pairs
     */
    public static function calculatePriceCorrelation(int $tradingPair1Id, int $tradingPair2Id, string $tradeType, int $hours = 24): float
    {
        $startTime = Carbon::now()->subHours($hours);
        
        $prices1 = self::forTradingPair($tradingPair1Id)
            ->forTradeType($tradeType)
            ->where('recorded_at', '>=', $startTime)
            ->orderBy('recorded_at', 'asc')
            ->pluck('best_price', 'recorded_at')
            ->toArray();

        $prices2 = self::forTradingPair($tradingPair2Id)
            ->forTradeType($tradeType)
            ->where('recorded_at', '>=', $startTime)
            ->orderBy('recorded_at', 'asc')
            ->pluck('best_price', 'recorded_at')
            ->toArray();

        if (empty($prices1) || empty($prices2)) {
            return 0;
        }

        // Align timestamps and get common data points
        $commonTimestamps = array_intersect(array_keys($prices1), array_keys($prices2));
        
        if (count($commonTimestamps) < 2) {
            return 0;
        }

        $alignedPrices1 = [];
        $alignedPrices2 = [];
        
        foreach ($commonTimestamps as $timestamp) {
            $alignedPrices1[] = $prices1[$timestamp];
            $alignedPrices2[] = $prices2[$timestamp];
        }

        return $this->calculatePearsonCorrelation($alignedPrices1, $alignedPrices2);
    }

    /**
     * Get liquidity analysis over time
     */
    public static function getLiquidityAnalysis(int $tradingPairId, int $hours = 24): array
    {
        $startTime = Carbon::now()->subHours($hours);
        
        $priceHistory = self::forTradingPair($tradingPairId)
            ->where('recorded_at', '>=', $startTime)
            ->orderBy('recorded_at', 'asc')
            ->get(['recorded_at', 'total_volume', 'liquidity_score', 'price_spread_percentage', 'merchant_count']);

        if ($priceHistory->isEmpty()) {
            return [
                'avg_liquidity_score' => null,
                'liquidity_trend' => 'unknown',
                'volume_stability' => null,
                'spread_stability' => null,
            ];
        }

        $liquidityScores = $priceHistory->whereNotNull('liquidity_score')->pluck('liquidity_score')->toArray();
        $volumes = $priceHistory->pluck('total_volume')->toArray();
        $spreads = $priceHistory->whereNotNull('price_spread_percentage')->pluck('price_spread_percentage')->toArray();

        $avgLiquidityScore = !empty($liquidityScores) ? array_sum($liquidityScores) / count($liquidityScores) : null;
        
        // Calculate trends
        $liquidityTrend = 'stable';
        if (count($liquidityScores) > 1) {
            $firstScore = $liquidityScores[0];
            $lastScore = end($liquidityScores);
            $change = $firstScore > 0 ? (($lastScore - $firstScore) / $firstScore) * 100 : 0;
            
            if ($change > 5) {
                $liquidityTrend = 'improving';
            } elseif ($change < -5) {
                $liquidityTrend = 'deteriorating';
            }
        }

        // Calculate stability metrics (coefficient of variation)
        $volumeStability = null;
        if (count($volumes) > 1 && array_sum($volumes) > 0) {
            $volumeMean = array_sum($volumes) / count($volumes);
            $volumeStdDev = $this->calculateStandardDeviation($volumes);
            $volumeStability = $volumeMean > 0 ? (1 - ($volumeStdDev / $volumeMean)) * 100 : 0;
        }

        $spreadStability = null;
        if (count($spreads) > 1 && array_sum($spreads) > 0) {
            $spreadMean = array_sum($spreads) / count($spreads);
            $spreadStdDev = $this->calculateStandardDeviation($spreads);
            $spreadStability = $spreadMean > 0 ? (1 - ($spreadStdDev / $spreadMean)) * 100 : 0;
        }

        return [
            'avg_liquidity_score' => $avgLiquidityScore ? round($avgLiquidityScore, 4) : null,
            'liquidity_trend' => $liquidityTrend,
            'volume_stability' => $volumeStability ? round($volumeStability, 2) : null,
            'spread_stability' => $spreadStability ? round($spreadStability, 2) : null,
            'avg_volume' => round(array_sum($volumes) / count($volumes), 2),
            'avg_spread' => !empty($spreads) ? round(array_sum($spreads) / count($spreads), 2) : null,
            'data_points' => $priceHistory->count(),
        ];
    }

    /**
     * Calculate moving averages
     */
    public static function getMovingAverages(int $tradingPairId, string $tradeType, array $periods = [5, 10, 20, 50]): array
    {
        $maxPeriod = max($periods);
        
        $priceHistory = self::forTradingPair($tradingPairId)
            ->forTradeType($tradeType)
            ->orderBy('recorded_at', 'desc')
            ->limit($maxPeriod)
            ->get(['best_price', 'recorded_at']);

        if ($priceHistory->count() < min($periods)) {
            return [];
        }

        $prices = $priceHistory->pluck('best_price')->reverse()->values()->toArray();
        $movingAverages = [];

        foreach ($periods as $period) {
            if (count($prices) >= $period) {
                $recentPrices = array_slice($prices, -$period);
                $ma = array_sum($recentPrices) / count($recentPrices);
                $movingAverages["MA{$period}"] = round($ma, 2);
            }
        }

        // Calculate current price position relative to moving averages
        $currentPrice = end($prices);
        $signals = [];
        
        foreach ($movingAverages as $period => $ma) {
            if ($currentPrice > $ma) {
                $signals[$period] = 'bullish';
            } elseif ($currentPrice < $ma) {
                $signals[$period] = 'bearish';
            } else {
                $signals[$period] = 'neutral';
            }
        }

        return [
            'moving_averages' => $movingAverages,
            'current_price' => $currentPrice,
            'signals' => $signals,
        ];
    }

    /**
     * Helper methods for statistical calculations
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

    private function calculatePearsonCorrelation(array $x, array $y): float
    {
        $n = count($x);
        if ($n !== count($y) || $n < 2) {
            return 0;
        }

        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumX2 = 0;
        $sumY2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumX2 += $x[$i] * $x[$i];
            $sumY2 += $y[$i] * $y[$i];
        }

        $numerator = $n * $sumXY - $sumX * $sumY;
        $denominator = sqrt(($n * $sumX2 - $sumX * $sumX) * ($n * $sumY2 - $sumY * $sumY));

        if ($denominator == 0) {
            return 0;
        }

        return $numerator / $denominator;
    }

    private function calculateOHLCVForInterval(int $timestamp, array $intervalData): array
    {
        $prices = array_column($intervalData, 'best_price');
        $volumes = array_column($intervalData, 'total_volume');

        return [
            'timestamp' => $timestamp,
            'open' => $prices[0],
            'high' => max($prices),
            'low' => min($prices),
            'close' => end($prices),
            'volume' => array_sum($volumes),
            'data_points' => count($intervalData),
        ];
    }

    private function calculateLevelStrength(array $prices, float $level, float $tolerance): int
    {
        $strength = 0;
        $range = $level * ($tolerance / 100);

        foreach ($prices as $price) {
            if (abs($price - $level) <= $range) {
                $strength++;
            }
        }

        return $strength;
    }

    /**
     * Get formatted price data for API responses
     */
    public function getFormattedData(): array
    {
        return [
            'id' => $this->id,
            'trading_pair_id' => $this->trading_pair_id,
            'recorded_at' => $this->recorded_at->toISOString(),
            'trade_type' => $this->trade_type,
            'prices' => [
                'best' => $this->best_price,
                'average' => $this->avg_price,
                'worst' => $this->worst_price,
                'median' => $this->median_price,
                'spread' => $this->price_spread,
                'spread_percentage' => $this->price_spread_percentage,
            ],
            'volume' => [
                'total' => $this->total_volume,
                'total_fiat' => $this->total_fiat_volume,
            ],
            'market' => [
                'active_orders' => $this->active_orders,
                'merchant_count' => $this->merchant_count,
                'pro_merchant_count' => $this->pro_merchant_count,
                'liquidity_score' => $this->liquidity_score,
            ],
            'quality' => [
                'data_quality_score' => $this->data_quality_score,
                'avg_completion_rate' => $this->avg_completion_rate,
                'avg_pay_time' => $this->avg_pay_time,
            ],
        ];
    }
}