<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class MarketSummaryCache extends Model
{
    use HasFactory;

    protected $fillable = [
        'trading_pair_id',
        'last_updated',
        'current_buy_price',
        'current_sell_price',
        'current_spread',
        'current_spread_percentage',
        'price_change_24h',
        'price_change_24h_percentage',
        'volume_24h',
        'high_24h',
        'low_24h',
        'avg_liquidity_score',
        'active_merchants_count',
        'market_efficiency_score',
        'trend_1h',
        'trend_4h',
        'trend_24h',
    ];

    protected $casts = [
        'last_updated' => 'datetime',
        'current_buy_price' => 'decimal:8',
        'current_sell_price' => 'decimal:8',
        'current_spread' => 'decimal:8',
        'current_spread_percentage' => 'decimal:4',
        'price_change_24h' => 'decimal:8',
        'price_change_24h_percentage' => 'decimal:4',
        'volume_24h' => 'decimal:8',
        'high_24h' => 'decimal:8',
        'low_24h' => 'decimal:8',
        'avg_liquidity_score' => 'decimal:4',
        'active_merchants_count' => 'integer',
        'market_efficiency_score' => 'decimal:4',
    ];

    /**
     * Get the trading pair that owns this summary cache
     */
    public function tradingPair(): BelongsTo
    {
        return $this->belongsTo(TradingPair::class);
    }

    /**
     * Update cache with latest market data
     */
    public function refreshCache(): void
    {
        $tradingPair = $this->tradingPair;
        
        // Get latest market data
        $latestData = $tradingPair->getLatestMarketData();
        
        if ($latestData) {
            $this->update([
                'current_buy_price' => $latestData['buy']->getPriceStatistics()['best_price'] ?? null,
                'current_sell_price' => $latestData['sell']->getPriceStatistics()['best_price'] ?? null,
                'current_spread' => $latestData['spread']['absolute'] ?? null,
                'current_spread_percentage' => $latestData['spread']['percentage'] ?? null,
                'last_updated' => now(),
            ]);
        }

        // Calculate 24h metrics
        $this->calculate24hMetrics();
        
        // Calculate trends
        $this->calculateTrends();
        
        // Calculate market health metrics
        $this->calculateMarketHealth();
    }

    /**
     * Calculate 24-hour metrics
     */
    private function calculate24hMetrics(): void
    {
        $tradingPair = $this->tradingPair;
        $priceHistory24h = $tradingPair->recentPriceHistory(24)->get();
        
        if ($priceHistory24h->isNotEmpty()) {
            $buyHistory = $priceHistory24h->where('trade_type', 'BUY');
            $sellHistory = $priceHistory24h->where('trade_type', 'SELL');
            
            // Calculate 24h price change (using buy prices)
            if ($buyHistory->isNotEmpty()) {
                $oldestPrice = $buyHistory->last()->best_price;
                $newestPrice = $buyHistory->first()->best_price;
                $priceChange = $newestPrice - $oldestPrice;
                $priceChangePercentage = $oldestPrice > 0 ? ($priceChange / $oldestPrice) * 100 : 0;
                
                $this->update([
                    'price_change_24h' => $priceChange,
                    'price_change_24h_percentage' => $priceChangePercentage,
                    'high_24h' => $buyHistory->max('best_price'),
                    'low_24h' => $buyHistory->min('best_price'),
                ]);
            }
            
            // Calculate 24h volume
            $totalVolume = $priceHistory24h->sum('total_volume');
            $this->update(['volume_24h' => $totalVolume]);
        }
    }

    /**
     * Calculate trend indicators for different timeframes
     */
    private function calculateTrends(): void
    {
        $tradingPair = $this->tradingPair;
        
        $trends = [
            'trend_1h' => $this->calculateTrendForPeriod($tradingPair, 1),
            'trend_4h' => $this->calculateTrendForPeriod($tradingPair, 4),
            'trend_24h' => $this->calculateTrendForPeriod($tradingPair, 24),
        ];
        
        $this->update($trends);
    }

    /**
     * Calculate trend for a specific period
     */
    private function calculateTrendForPeriod(TradingPair $tradingPair, int $hours): string
    {
        $trendData = $tradingPair->getPriceTrend($hours, 'BUY');
        
        $changePercentage = $trendData['change_percentage'] ?? 0;
        
        if ($changePercentage > 1) {
            return 'up';
        } elseif ($changePercentage < -1) {
            return 'down';
        }
        
        return 'stable';
    }

    /**
     * Calculate market health metrics
     */
    private function calculateMarketHealth(): void
    {
        $tradingPair = $this->tradingPair;
        $liquidityMetrics = $tradingPair->getLiquidityMetrics(24);
        
        // Get recent market statistics for efficiency score
        $recentStats = MarketStatistics::forTradingPair($this->trading_pair_id)
            ->recent(24)
            ->orderBy('period_start', 'desc')
            ->first();
        
        $this->update([
            'avg_liquidity_score' => $liquidityMetrics['avg_liquidity_score'],
            'active_merchants_count' => $liquidityMetrics['active_periods'] ?? 0,
            'market_efficiency_score' => $recentStats?->market_efficiency,
        ]);
    }

    /**
     * Check if cache needs refresh
     */
    public function needsRefresh(int $maxAgeMinutes = 5): bool
    {
        if (!$this->last_updated) {
            return true;
        }
        
        return $this->last_updated->diffInMinutes(now()) >= $maxAgeMinutes;
    }

    /**
     * Get formatted cache data for API
     */
    public function getFormattedData(): array
    {
        return [
            'trading_pair' => $this->tradingPair->pair_symbol,
            'last_updated' => $this->last_updated?->toISOString(),
            'current_prices' => [
                'buy' => $this->current_buy_price,
                'sell' => $this->current_sell_price,
                'spread' => $this->current_spread,
                'spread_percentage' => $this->current_spread_percentage,
            ],
            'price_changes_24h' => [
                'absolute' => $this->price_change_24h,
                'percentage' => $this->price_change_24h_percentage,
                'high' => $this->high_24h,
                'low' => $this->low_24h,
            ],
            'volume_24h' => $this->volume_24h,
            'market_health' => [
                'liquidity_score' => $this->avg_liquidity_score,
                'active_merchants' => $this->active_merchants_count,
                'efficiency_score' => $this->market_efficiency_score,
            ],
            'trends' => [
                '1h' => $this->trend_1h,
                '4h' => $this->trend_4h,
                '24h' => $this->trend_24h,
            ],
        ];
    }
}