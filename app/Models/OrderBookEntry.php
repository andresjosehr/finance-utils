<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderBookEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'p2p_market_snapshot_id',
        'side',
        'price',
        'quantity',
        'total_amount',
        'min_order_limit',
        'max_order_limit',
        'merchant_name',
        'merchant_id',
        'completion_rate',
        'trade_count',
        'payment_methods',
        'merchant_metadata',
        'is_pro_merchant',
        'is_kyc_verified',
        'avg_pay_time',
        'avg_release_time',
    ];

    protected $casts = [
        'price' => 'decimal:8',
        'quantity' => 'decimal:8',
        'total_amount' => 'decimal:8',
        'min_order_limit' => 'decimal:8',
        'max_order_limit' => 'decimal:8',
        'completion_rate' => 'integer',
        'trade_count' => 'integer',
        'payment_methods' => 'array',
        'merchant_metadata' => 'array',
        'is_pro_merchant' => 'boolean',
        'is_kyc_verified' => 'boolean',
        'avg_pay_time' => 'decimal:2',
        'avg_release_time' => 'decimal:2',
    ];

    /**
     * Get the market snapshot that owns this order book entry
     */
    public function marketSnapshot(): BelongsTo
    {
        return $this->belongsTo(P2PMarketSnapshot::class, 'p2p_market_snapshot_id');
    }

    /**
     * Get the trading pair through the market snapshot
     */
    public function tradingPair(): BelongsTo
    {
        return $this->marketSnapshot->tradingPair();
    }

    /**
     * Scope to filter by side (bid/ask)
     */
    public function scopeForSide(Builder $query, string $side): Builder
    {
        return $query->where('side', strtolower($side));
    }

    /**
     * Scope to filter by price range
     */
    public function scopePriceBetween(Builder $query, float $minPrice, float $maxPrice): Builder
    {
        return $query->whereBetween('price', [$minPrice, $maxPrice]);
    }

    /**
     * Scope to filter by merchant
     */
    public function scopeForMerchant(Builder $query, string $merchantId): Builder
    {
        return $query->where('merchant_id', $merchantId);
    }

    /**
     * Scope to filter pro merchants only
     */
    public function scopeProMerchantsOnly(Builder $query): Builder
    {
        return $query->where('is_pro_merchant', true);
    }

    /**
     * Scope to filter KYC verified merchants only
     */
    public function scopeKycVerifiedOnly(Builder $query): Builder
    {
        return $query->where('is_kyc_verified', true);
    }

    /**
     * Scope to filter by minimum completion rate
     */
    public function scopeMinCompletionRate(Builder $query, int $minRate): Builder
    {
        return $query->where('completion_rate', '>=', $minRate);
    }

    /**
     * Scope to filter by minimum volume
     */
    public function scopeMinVolume(Builder $query, float $minVolume): Builder
    {
        return $query->where('quantity', '>=', $minVolume);
    }

    /**
     * Scope to filter by payment methods
     */
    public function scopeWithPaymentMethod(Builder $query, string $method): Builder
    {
        return $query->whereJsonContains('payment_methods', ['identifier' => $method]);
    }

    /**
     * Scope to get entries from recent snapshots
     */
    public function scopeRecent(Builder $query, int $hours = 24): Builder
    {
        $recentSnapshotIds = P2PMarketSnapshot::where('collected_at', '>=', Carbon::now()->subHours($hours))
            ->pluck('id');

        return $query->whereIn('p2p_market_snapshot_id', $recentSnapshotIds);
    }

    /**
     * Get order book depth analysis for a specific side
     */
    public static function getDepthAnalysis(int $tradingPairId, string $side, int $hours = 1): array
    {
        // Get the most recent snapshot for analysis
        $latestSnapshot = P2PMarketSnapshot::where('trading_pair_id', $tradingPairId)
            ->where('collected_at', '>=', Carbon::now()->subHours($hours))
            ->orderBy('collected_at', 'desc')
            ->first();

        if (! $latestSnapshot) {
            return [
                'depth' => [],
                'total_volume' => 0,
                'total_value' => 0,
                'average_order_size' => 0,
                'price_levels' => 0,
            ];
        }

        $entries = self::where('p2p_market_snapshot_id', $latestSnapshot->id)
            ->where('side', strtolower($side))
            ->orderBy('price', $side === 'bid' ? 'desc' : 'asc')
            ->get();

        if ($entries->isEmpty()) {
            return [
                'depth' => [],
                'total_volume' => 0,
                'total_value' => 0,
                'average_order_size' => 0,
                'price_levels' => 0,
            ];
        }

        $depth = [];
        $cumulativeVolume = 0;
        $cumulativeValue = 0;

        foreach ($entries as $entry) {
            $cumulativeVolume += $entry->quantity;
            $value = $entry->price * $entry->quantity;
            $cumulativeValue += $value;

            $depth[] = [
                'price' => $entry->price,
                'quantity' => $entry->quantity,
                'total_amount' => $entry->total_amount,
                'cumulative_volume' => $cumulativeVolume,
                'cumulative_value' => $cumulativeValue,
                'merchant_id' => $entry->merchant_id,
                'merchant_name' => $entry->merchant_name,
                'is_pro_merchant' => $entry->is_pro_merchant,
                'completion_rate' => $entry->completion_rate,
                'payment_methods' => $entry->payment_methods,
            ];
        }

        return [
            'depth' => $depth,
            'total_volume' => $cumulativeVolume,
            'total_value' => $cumulativeValue,
            'average_order_size' => $entries->avg('quantity'),
            'price_levels' => $entries->count(),
            'best_price' => $entries->first()->price,
            'worst_price' => $entries->last()->price,
            'price_spread' => abs($entries->first()->price - $entries->last()->price),
        ];
    }

    /**
     * Get merchant performance analysis
     */
    public static function getMerchantPerformanceAnalysis(string $merchantId, int $days = 30): array
    {
        $cutoffDate = Carbon::now()->subDays($days);

        $entries = self::whereHas('marketSnapshot', function ($query) use ($cutoffDate) {
            $query->where('collected_at', '>=', $cutoffDate);
        })
            ->where('merchant_id', $merchantId)
            ->with('marketSnapshot')
            ->get();

        if ($entries->isEmpty()) {
            return [
                'merchant_id' => $merchantId,
                'total_orders' => 0,
                'avg_price' => null,
                'total_volume' => 0,
                'market_share' => 0,
                'price_competitiveness' => null,
                'activity_frequency' => 0,
            ];
        }

        $totalOrders = $entries->count();
        $totalVolume = $entries->sum('quantity');
        $avgPrice = $entries->avg('price');

        // Calculate market share (simplified - based on volume)
        $totalMarketVolume = self::whereHas('marketSnapshot', function ($query) use ($cutoffDate) {
            $query->where('collected_at', '>=', $cutoffDate);
        })
            ->sum('quantity');

        $marketShare = $totalMarketVolume > 0 ? ($totalVolume / $totalMarketVolume) * 100 : 0;

        // Calculate activity frequency (unique days with orders)
        $activeDays = $entries->groupBy(function ($entry) {
            return $entry->marketSnapshot->collected_at->format('Y-m-d');
        })->count();

        // Price competitiveness (how often this merchant has the best price)
        $bestPriceCount = 0;
        $snapshotGroups = $entries->groupBy('p2p_market_snapshot_id');

        foreach ($snapshotGroups as $snapshotEntries) {
            $merchantPrice = $snapshotEntries->first()->price;
            $snapshot = $snapshotEntries->first()->marketSnapshot;

            $allEntriesInSnapshot = self::where('p2p_market_snapshot_id', $snapshot->id)
                ->where('side', $snapshotEntries->first()->side)
                ->get();

            $bestPrice = $snapshot->trade_type === 'BUY'
                ? $allEntriesInSnapshot->max('price')  // Highest buy price is best
                : $allEntriesInSnapshot->min('price'); // Lowest sell price is best

            if ($merchantPrice == $bestPrice) {
                $bestPriceCount++;
            }
        }

        $priceCompetitiveness = $snapshotGroups->count() > 0
            ? ($bestPriceCount / $snapshotGroups->count()) * 100
            : 0;

        return [
            'merchant_id' => $merchantId,
            'merchant_name' => $entries->first()->merchant_name,
            'total_orders' => $totalOrders,
            'avg_price' => round($avgPrice, 2),
            'total_volume' => round($totalVolume, 2),
            'market_share' => round($marketShare, 4),
            'price_competitiveness' => round($priceCompetitiveness, 2),
            'activity_frequency' => $activeDays,
            'avg_completion_rate' => $entries->avg('completion_rate'),
            'avg_pay_time' => $entries->avg('avg_pay_time'),
            'is_pro_merchant' => $entries->first()->is_pro_merchant,
            'is_kyc_verified' => $entries->first()->is_kyc_verified,
        ];
    }

    /**
     * Get payment method distribution analysis
     */
    public static function getPaymentMethodAnalysis(int $tradingPairId, int $hours = 24): array
    {
        $cutoffTime = Carbon::now()->subHours($hours);

        $entries = self::whereHas('marketSnapshot', function ($query) use ($tradingPairId, $cutoffTime) {
            $query->where('trading_pair_id', $tradingPairId)
                ->where('collected_at', '>=', $cutoffTime);
        })
            ->whereNotNull('payment_methods')
            ->get();

        $methodCounts = [];
        $methodVolumes = [];
        $totalVolume = 0;

        foreach ($entries as $entry) {
            $volume = $entry->quantity;
            $totalVolume += $volume;

            if (is_array($entry->payment_methods)) {
                foreach ($entry->payment_methods as $method) {
                    $identifier = $method['identifier'] ?? 'unknown';
                    $name = $method['trade_method_name'] ?? $identifier;

                    if (! isset($methodCounts[$identifier])) {
                        $methodCounts[$identifier] = [
                            'identifier' => $identifier,
                            'name' => $name,
                            'count' => 0,
                            'volume' => 0,
                        ];
                    }

                    $methodCounts[$identifier]['count']++;
                    $methodCounts[$identifier]['volume'] += $volume;
                }
            }
        }

        // Calculate percentages and sort by volume
        $methodStats = [];
        foreach ($methodCounts as $method) {
            $methodStats[] = [
                'identifier' => $method['identifier'],
                'name' => $method['name'],
                'count' => $method['count'],
                'volume' => round($method['volume'], 2),
                'volume_percentage' => $totalVolume > 0 ? round(($method['volume'] / $totalVolume) * 100, 2) : 0,
                'count_percentage' => $entries->count() > 0 ? round(($method['count'] / $entries->count()) * 100, 2) : 0,
            ];
        }

        // Sort by volume descending
        usort($methodStats, function ($a, $b) {
            return $b['volume'] <=> $a['volume'];
        });

        return [
            'total_orders' => $entries->count(),
            'total_volume' => round($totalVolume, 2),
            'unique_methods' => count($methodStats),
            'methods' => $methodStats,
        ];
    }

    /**
     * Get liquidity concentration analysis
     */
    public static function getLiquidityConcentration(int $tradingPairId, string $side, int $hours = 1): array
    {
        $latestSnapshot = P2PMarketSnapshot::where('trading_pair_id', $tradingPairId)
            ->where('collected_at', '>=', Carbon::now()->subHours($hours))
            ->orderBy('collected_at', 'desc')
            ->first();

        if (! $latestSnapshot) {
            return [
                'total_volume' => 0,
                'top_5_concentration' => 0,
                'top_10_concentration' => 0,
                'merchant_concentration' => 0,
                'herfindahl_index' => 0,
            ];
        }

        $entries = self::where('p2p_market_snapshot_id', $latestSnapshot->id)
            ->where('side', strtolower($side))
            ->orderBy('quantity', 'desc')
            ->get();

        if ($entries->isEmpty()) {
            return [
                'total_volume' => 0,
                'top_5_concentration' => 0,
                'top_10_concentration' => 0,
                'merchant_concentration' => 0,
                'herfindahl_index' => 0,
            ];
        }

        $totalVolume = $entries->sum('quantity');
        $top5Volume = $entries->take(5)->sum('quantity');
        $top10Volume = $entries->take(10)->sum('quantity');

        // Calculate merchant concentration (volume by unique merchants)
        $merchantVolumes = $entries->groupBy('merchant_id')->map(function ($group) {
            return $group->sum('quantity');
        })->sortDesc();

        $topMerchantVolume = $merchantVolumes->take(5)->sum();

        // Calculate Herfindahl-Hirschman Index for market concentration
        $hhi = 0;
        foreach ($merchantVolumes as $volume) {
            $marketShare = $totalVolume > 0 ? $volume / $totalVolume : 0;
            $hhi += $marketShare * $marketShare;
        }
        $hhi *= 10000; // Scale to standard HHI range

        return [
            'total_volume' => round($totalVolume, 2),
            'top_5_concentration' => $totalVolume > 0 ? round(($top5Volume / $totalVolume) * 100, 2) : 0,
            'top_10_concentration' => $totalVolume > 0 ? round(($top10Volume / $totalVolume) * 100, 2) : 0,
            'merchant_concentration' => $totalVolume > 0 ? round(($topMerchantVolume / $totalVolume) * 100, 2) : 0,
            'herfindahl_index' => round($hhi, 2),
            'unique_merchants' => $merchantVolumes->count(),
            'total_orders' => $entries->count(),
        ];
    }

    /**
     * Calculate order value in fiat currency
     */
    public function getOrderValueAttribute(): float
    {
        return $this->price * $this->quantity;
    }

    /**
     * Get formatted payment methods for display
     */
    public function getFormattedPaymentMethodsAttribute(): string
    {
        if (! is_array($this->payment_methods) || empty($this->payment_methods)) {
            return 'N/A';
        }

        $methods = array_map(function ($method) {
            return $method['trade_method_name'] ?? $method['identifier'] ?? 'Unknown';
        }, $this->payment_methods);

        return implode(', ', array_unique($methods));
    }

    /**
     * Check if this order meets quality criteria
     */
    public function meetsQualityCriteria(array $criteria = []): bool
    {
        $defaults = [
            'min_completion_rate' => 85,
            'min_trade_count' => 10,
            'require_kyc' => false,
            'require_pro_merchant' => false,
            'max_pay_time' => 30, // minutes
        ];

        $criteria = array_merge($defaults, $criteria);

        if ($this->completion_rate < $criteria['min_completion_rate']) {
            return false;
        }

        if ($this->trade_count < $criteria['min_trade_count']) {
            return false;
        }

        if ($criteria['require_kyc'] && ! $this->is_kyc_verified) {
            return false;
        }

        if ($criteria['require_pro_merchant'] && ! $this->is_pro_merchant) {
            return false;
        }

        if ($this->avg_pay_time && $this->avg_pay_time > $criteria['max_pay_time']) {
            return false;
        }

        return true;
    }

    /**
     * Get trust score for this merchant (0-100)
     */
    public function getMerchantTrustScore(): int
    {
        $score = 0;

        // Completion rate (40% weight)
        if ($this->completion_rate) {
            $score += ($this->completion_rate * 0.4);
        }

        // Trade count (20% weight)
        if ($this->trade_count) {
            $tradeScore = min(100, ($this->trade_count / 100) * 100); // Cap at 100 trades for max score
            $score += ($tradeScore * 0.2);
        }

        // Pro merchant status (15% weight)
        if ($this->is_pro_merchant) {
            $score += 15;
        }

        // KYC verification (15% weight)
        if ($this->is_kyc_verified) {
            $score += 15;
        }

        // Payment time (10% weight) - faster is better
        if ($this->avg_pay_time) {
            $timeScore = max(0, 100 - ($this->avg_pay_time * 2)); // Penalize slow payment times
            $score += ($timeScore * 0.1);
        } else {
            $score += 10; // Default if no data
        }

        return min(100, max(0, round($score)));
    }
}
