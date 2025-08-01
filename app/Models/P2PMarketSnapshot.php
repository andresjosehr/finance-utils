<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class P2PMarketSnapshot extends Model
{
    use HasFactory;

    protected $table = 'p2p_market_snapshots';

    protected $fillable = [
        'trading_pair_id',
        'trade_type',
        'collected_at',
        'raw_data',
        'total_ads',
        'data_quality_score',
        'collection_metadata',
    ];

    protected $casts = [
        'collected_at' => 'datetime',
        'raw_data' => 'array',
        'collection_metadata' => 'array',
        'data_quality_score' => 'decimal:4',
        'total_ads' => 'integer',
    ];

    /**
     * Get the trading pair that owns this snapshot
     */
    public function tradingPair(): BelongsTo
    {
        return $this->belongsTo(TradingPair::class);
    }

    /**
     * Get all order book entries for this snapshot
     */
    public function orderBookEntries(): HasMany
    {
        return $this->hasMany(OrderBookEntry::class);
    }

    /**
     * Scope to filter by trade type
     */
    public function scopeForTradeType(Builder $query, string $tradeType): Builder
    {
        return $query->where('trade_type', strtoupper($tradeType));
    }

    /**
     * Scope to filter by trading pair
     */
    public function scopeForTradingPair(Builder $query, int $tradingPairId): Builder
    {
        return $query->where('trading_pair_id', $tradingPairId);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeBetweenDates(Builder $query, Carbon $startDate, Carbon $endDate): Builder
    {
        return $query->whereBetween('collected_at', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by data quality
     */
    public function scopeHighQuality(Builder $query, float $threshold = 0.8): Builder
    {
        return $query->where('data_quality_score', '>=', $threshold);
    }

    /**
     * Scope to get recent snapshots
     */
    public function scopeRecent(Builder $query, int $hours = 24): Builder
    {
        return $query->where('collected_at', '>=', Carbon::now()->subHours($hours));
    }

    /**
     * Process raw data and extract order book entries
     */
    public function processRawData(): void
    {
        if (empty($this->raw_data['data'])) {
            return;
        }

        // Clear existing entries
        $this->orderBookEntries()->delete();

        $side = $this->trade_type === 'BUY' ? 'bid' : 'ask';

        foreach ($this->raw_data['data'] as $order) {
            $adv = $order['adv'] ?? [];
            $advertiser = $order['advertiser'] ?? [];

            OrderBookEntry::create([
                'p2p_market_snapshot_id' => $this->id,
                'side' => $side,
                'price' => $adv['price'] ?? 0,
                'quantity' => $adv['surplusAmount'] ?? 0,
                'total_amount' => $adv['tradableQuantity'] ?? 0,
                'min_order_limit' => $adv['minSingleTransAmount'] ?? null,
                'max_order_limit' => $adv['maxSingleTransAmount'] ?? null,
                'merchant_name' => $advertiser['nickName'] ?? null,
                'merchant_id' => $advertiser['userNo'] ?? null,
                'completion_rate' => $this->parseCompletionRate($advertiser['orderCompletionRate'] ?? null),
                'trade_count' => $advertiser['monthOrderCount'] ?? null,
                'payment_methods' => $this->extractPaymentMethods($adv),
                'merchant_metadata' => $this->extractMerchantMetadata($advertiser),
                'is_pro_merchant' => ($advertiser['userType'] ?? '') === 'merchant',
                'is_kyc_verified' => $this->isKycVerified($advertiser),
                'avg_pay_time' => $this->parseAvgTime($advertiser['avgPayTime'] ?? null),
                'avg_release_time' => $this->parseAvgTime($advertiser['avgReleaseTime'] ?? null),
            ]);
        }

        // Update total ads count
        $this->update(['total_ads' => count($this->raw_data['data'])]);
    }

    /**
     * Calculate data quality score based on various factors
     */
    public function calculateDataQualityScore(): float
    {
        $score = 1.0;
        $factors = [];

        // Check if we have data
        if (empty($this->raw_data['data'])) {
            return 0.0;
        }

        $dataCount = count($this->raw_data['data']);
        $factors['data_count'] = min($dataCount / 20, 1.0); // Expect at least 20 orders

        // Check response time
        $responseTime = $this->collection_metadata['response_time_ms'] ?? 1000;
        $factors['response_time'] = max(0, min(1.0, (5000 - $responseTime) / 5000)); // Penalize slow responses

        // Check for missing critical fields
        $validOrders = 0;
        foreach ($this->raw_data['data'] as $order) {
            if (isset($order['adv']['price']) &&
                isset($order['adv']['surplusAmount']) &&
                isset($order['advertiser']['userNo'])) {
                $validOrders++;
            }
        }
        $factors['data_completeness'] = $dataCount > 0 ? $validOrders / $dataCount : 0;

        // Check for API errors
        if (isset($this->raw_data['code']) && $this->raw_data['code'] !== '000000') {
            $factors['api_error'] = 0.5; // Significant penalty for API errors
        } else {
            $factors['api_error'] = 1.0;
        }

        // Check collection timing (penalize if too old or future)
        $collectionAge = abs(Carbon::now()->diffInMinutes($this->collected_at));
        $factors['timing'] = max(0, min(1.0, (60 - $collectionAge) / 60)); // Penalize if older than 1 hour

        // Calculate weighted average
        $weights = [
            'data_count' => 0.3,
            'response_time' => 0.1,
            'data_completeness' => 0.4,
            'api_error' => 0.15,
            'timing' => 0.05,
        ];

        $weightedScore = 0;
        foreach ($factors as $factor => $value) {
            $weightedScore += $value * ($weights[$factor] ?? 0);
        }

        return round($weightedScore, 4);
    }

    /**
     * Get basic price statistics from raw data
     */
    public function getPriceStatistics(): array
    {
        if (empty($this->raw_data['data'])) {
            return [
                'best_price' => null,
                'avg_price' => null,
                'worst_price' => null,
                'total_volume' => 0,
                'order_count' => 0,
            ];
        }

        $prices = [];
        $volumes = [];

        foreach ($this->raw_data['data'] as $order) {
            $price = (float) ($order['adv']['price'] ?? 0);
            $volume = (float) ($order['adv']['surplusAmount'] ?? 0);

            if ($price > 0) {
                $prices[] = $price;
                $volumes[] = $volume;
            }
        }

        if (empty($prices)) {
            return [
                'best_price' => null,
                'avg_price' => null,
                'worst_price' => null,
                'total_volume' => 0,
                'order_count' => 0,
            ];
        }

        return [
            'best_price' => min($prices),
            'avg_price' => array_sum($prices) / count($prices),
            'worst_price' => max($prices),
            'total_volume' => array_sum($volumes),
            'order_count' => count($prices),
            'median_price' => $this->calculateMedian($prices),
            'price_spread' => max($prices) - min($prices),
            'volume_weighted_price' => $this->calculateVWAP($prices, $volumes),
        ];
    }

    /**
     * Get merchant statistics from this snapshot
     */
    public function getMerchantStatistics(): array
    {
        if (empty($this->raw_data['data'])) {
            return [];
        }

        $merchants = [];
        $completionRates = [];
        $payTimes = [];
        $releaseTimes = [];
        $proMerchantCount = 0;

        foreach ($this->raw_data['data'] as $order) {
            $advertiser = $order['advertiser'] ?? [];
            $userNo = $advertiser['userNo'] ?? null;

            if ($userNo && ! in_array($userNo, $merchants)) {
                $merchants[] = $userNo;

                $completionRate = $this->parseCompletionRate($advertiser['orderCompletionRate'] ?? null);
                if ($completionRate !== null) {
                    $completionRates[] = $completionRate;
                }

                $payTime = $this->parseAvgTime($advertiser['avgPayTime'] ?? null);
                if ($payTime !== null) {
                    $payTimes[] = $payTime;
                }

                $releaseTime = $this->parseAvgTime($advertiser['avgReleaseTime'] ?? null);
                if ($releaseTime !== null) {
                    $releaseTimes[] = $releaseTime;
                }

                if (($advertiser['userType'] ?? '') === 'merchant') {
                    $proMerchantCount++;
                }
            }
        }

        return [
            'unique_merchants' => count($merchants),
            'pro_merchant_count' => $proMerchantCount,
            'avg_completion_rate' => ! empty($completionRates) ? array_sum($completionRates) / count($completionRates) : null,
            'avg_pay_time' => ! empty($payTimes) ? array_sum($payTimes) / count($payTimes) : null,
            'avg_release_time' => ! empty($releaseTimes) ? array_sum($releaseTimes) / count($releaseTimes) : null,
        ];
    }

    /**
     * Create a price history entry from this snapshot
     */
    public function createPriceHistoryEntry(): ?PriceHistory
    {
        $priceStats = $this->getPriceStatistics();
        $merchantStats = $this->getMerchantStatistics();

        if ($priceStats['best_price'] === null) {
            return null;
        }

        return PriceHistory::create([
            'trading_pair_id' => $this->trading_pair_id,
            'recorded_at' => $this->collected_at,
            'trade_type' => $this->trade_type,
            'best_price' => $priceStats['best_price'],
            'avg_price' => $priceStats['avg_price'],
            'worst_price' => $priceStats['worst_price'],
            'median_price' => $priceStats['median_price'] ?? null,
            'total_volume' => $priceStats['total_volume'],
            'active_orders' => $priceStats['order_count'],
            'merchant_count' => $merchantStats['unique_merchants'] ?? 0,
            'pro_merchant_count' => $merchantStats['pro_merchant_count'] ?? 0,
            'price_spread' => $priceStats['price_spread'] ?? 0,
            'price_spread_percentage' => $priceStats['best_price'] > 0 ?
                (($priceStats['price_spread'] ?? 0) / $priceStats['best_price']) * 100 : 0,
            'avg_completion_rate' => $merchantStats['avg_completion_rate'],
            'avg_pay_time' => $merchantStats['avg_pay_time'],
            'data_quality_score' => $this->data_quality_score,
        ]);
    }

    /**
     * Helper methods for data parsing
     */
    private function parseCompletionRate(?string $rate): ?int
    {
        if ($rate === null) {
            return null;
        }

        // Remove percentage sign and convert to integer
        return (int) str_replace('%', '', $rate);
    }

    private function parseAvgTime(?string $time): ?float
    {
        if ($time === null) {
            return null;
        }

        // Parse time strings like "1.5 min" or "30 sec"
        if (preg_match('/(\d+\.?\d*)\s*min/', $time, $matches)) {
            return (float) $matches[1];
        } elseif (preg_match('/(\d+\.?\d*)\s*sec/', $time, $matches)) {
            return (float) $matches[1] / 60; // Convert to minutes
        }

        return null;
    }

    private function extractPaymentMethods(array $adv): array
    {
        $methods = [];

        if (isset($adv['tradeMethods'])) {
            foreach ($adv['tradeMethods'] as $method) {
                $methods[] = [
                    'identifier' => $method['identifier'] ?? null,
                    'trade_method_name' => $method['tradeMethodName'] ?? null,
                ];
            }
        }

        return $methods;
    }

    private function extractMerchantMetadata(array $advertiser): array
    {
        return [
            'user_type' => $advertiser['userType'] ?? null,
            'user_grade' => $advertiser['userGrade'] ?? null,
            'month_order_count' => $advertiser['monthOrderCount'] ?? null,
            'month_finish_rate' => $advertiser['monthFinishRate'] ?? null,
            'positive_rate' => $advertiser['positiveRate'] ?? null,
        ];
    }

    private function isKycVerified(array $advertiser): bool
    {
        // This is a heuristic - adjust based on actual API response structure
        return isset($advertiser['userGrade']) && $advertiser['userGrade'] > 0;
    }

    private function calculateMedian(array $values): float
    {
        sort($values);
        $count = count($values);

        if ($count % 2 === 0) {
            return ($values[$count / 2 - 1] + $values[$count / 2]) / 2;
        } else {
            return $values[floor($count / 2)];
        }
    }

    private function calculateVWAP(array $prices, array $volumes): float
    {
        $totalValue = 0;
        $totalVolume = 0;

        for ($i = 0; $i < count($prices); $i++) {
            $value = $prices[$i] * ($volumes[$i] ?? 0);
            $totalValue += $value;
            $totalVolume += $volumes[$i] ?? 0;
        }

        return $totalVolume > 0 ? $totalValue / $totalVolume : 0;
    }

    /**
     * Update the data quality score
     */
    public function updateDataQualityScore(): void
    {
        $this->update(['data_quality_score' => $this->calculateDataQualityScore()]);
    }

    /**
     * Check if this snapshot is recent enough for analysis
     */
    public function isRecent(int $minutes = 30): bool
    {
        return $this->collected_at->diffInMinutes(Carbon::now()) <= $minutes;
    }

    /**
     * Get a summary of this snapshot for API responses
     */
    public function getSummary(): array
    {
        $priceStats = $this->getPriceStatistics();
        $merchantStats = $this->getMerchantStatistics();

        return [
            'id' => $this->id,
            'trading_pair' => $this->tradingPair->pair_symbol,
            'trade_type' => $this->trade_type,
            'collected_at' => $this->collected_at->toISOString(),
            'total_ads' => $this->total_ads,
            'data_quality_score' => $this->data_quality_score,
            'price_statistics' => $priceStats,
            'merchant_statistics' => $merchantStats,
        ];
    }

    /**
     * Calculate data quality score based on various factors
     */
    public function calculateQualityScore(): float
    {
        $score = 1.0;
        $factors = [];

        // Check if we have data
        if (empty($this->raw_data['data'] ?? [])) {
            return 0.0;
        }

        $ads = collect($this->raw_data['data']);
        $adsCount = $ads->count();

        // Factor 1: Number of ads (more ads = better quality)
        if ($adsCount < 5) {
            $factors['low_ad_count'] = -0.3;
        } elseif ($adsCount < 10) {
            $factors['medium_ad_count'] = -0.1;
        }

        // Factor 2: Price spread (lower spread = better quality)
        $prices = $ads->pluck('adv.price')->map(fn ($p) => (float) $p);
        if ($prices->count() > 1) {
            $spread = ($prices->max() - $prices->min()) / $prices->avg();
            if ($spread > 0.1) { // 10% spread
                $factors['high_spread'] = -0.2;
            } elseif ($spread > 0.05) { // 5% spread
                $factors['medium_spread'] = -0.1;
            }
        }

        // Factor 3: Data freshness (API response should be recent)
        $collectionAge = now()->diffInMinutes($this->collected_at);
        if ($collectionAge > 10) {
            $factors['stale_data'] = -0.1;
        }

        // Factor 4: Completeness of ad data
        $incompleteAds = $ads->filter(function ($ad) {
            return empty($ad['adv']['price']) ||
                   empty($ad['adv']['surplusAmount']) ||
                   empty($ad['advertiser']['nickName']);
        })->count();

        if ($incompleteAds > 0) {
            $factors['incomplete_data'] = -($incompleteAds / $adsCount) * 0.3;
        }

        // Apply all factors
        $finalScore = $score + array_sum($factors);

        return max(0.0, min(1.0, $finalScore));
    }

    /**
     * Update the quality score for this snapshot
     */
    public function updateQualityScore(): void
    {
        $this->update([
            'data_quality_score' => $this->calculateQualityScore(),
        ]);
    }

    /**
     * Get price metrics for this snapshot
     */
    public function getPriceMetrics(): array
    {
        $stats = $this->getPriceStatistics();

        // Ensure consistent field names for backwards compatibility
        return array_merge($stats, [
            'best' => $stats['best_price'],
            'average' => $stats['avg_price'],
            'worst' => $stats['worst_price'],
            'count' => $stats['order_count'],
            'spread' => $stats['price_spread'] ?? 0,
        ]);
    }
}
