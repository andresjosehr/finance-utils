<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TradingPair extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset',
        'fiat',
        'pair_symbol',
        'is_active',
        'collection_interval_minutes',
        'collection_config',
        'min_trade_amount',
        'max_trade_amount',
        'volume_ranges',
        'use_volume_sampling',
        'default_sample_volume',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'collection_config' => 'array',
        'min_trade_amount' => 'decimal:8',
        'max_trade_amount' => 'decimal:8',
        'collection_interval_minutes' => 'integer',
        'volume_ranges' => 'array',
        'use_volume_sampling' => 'boolean',
        'default_sample_volume' => 'decimal:2',
    ];

    /**
     * Get all market snapshots for this trading pair
     */
    public function marketSnapshots(): HasMany
    {
        return $this->hasMany(P2PMarketSnapshot::class);
    }

    /**
     * Get recent market snapshots
     */
    public function recentSnapshots(int $hours = 24): HasMany
    {
        return $this->marketSnapshots()
            ->where('collected_at', '>=', now()->subHours($hours))
            ->orderBy('collected_at', 'desc');
    }

    /**
     * Get the latest snapshot for a specific trade type
     */
    public function latestSnapshot(string $tradeType = 'BUY'): ?P2PMarketSnapshot
    {
        return $this->marketSnapshots()
            ->where('trade_type', $tradeType)
            ->orderBy('collected_at', 'desc')
            ->first();
    }

    /**
     * Check if data collection is due for this pair
     */
    public function isCollectionDue(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $latestSnapshot = $this->marketSnapshots()
            ->orderBy('collected_at', 'desc')
            ->first();

        if (! $latestSnapshot) {
            return true;
        }

        // For 1-minute intervals, always collect since Laravel schedule can't run faster than every minute
        if ($this->collection_interval_minutes == 1) {
            return true;
        }

        // For longer intervals, check if enough time has passed
        $nextCollection = $latestSnapshot->collected_at->addMinutes($this->collection_interval_minutes);

        return now()->greaterThanOrEqualTo($nextCollection);
    }

    /**
     * Create a trading pair from asset and fiat symbols
     */
    public static function createPair(
        string $asset,
        string $fiat,
        array $config = [],
        int $intervalMinutes = 5
    ): self {
        return self::create([
            'asset' => strtoupper($asset),
            'fiat' => strtoupper($fiat),
            'pair_symbol' => strtoupper($asset).'/'.strtoupper($fiat),
            'is_active' => true,
            'collection_interval_minutes' => $intervalMinutes,
            'collection_config' => $config,
        ]);
    }

    /**
     * Get all active trading pairs that need data collection
     */
    public static function needingCollection(): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('is_active', true)
            ->get()
            ->filter(fn ($pair) => $pair->isCollectionDue());
    }

    /**
     * Get the time until next collection is due
     */
    public function timeUntilNextCollection(): ?int
    {
        if (! $this->is_active) {
            return null;
        }

        $latestSnapshot = $this->marketSnapshots()
            ->orderBy('collected_at', 'desc')
            ->first();

        if (! $latestSnapshot) {
            return 0; // Collection due immediately
        }

        $nextCollection = $latestSnapshot->collected_at->addMinutes($this->collection_interval_minutes);
        $secondsUntilNext = now()->diffInSeconds($nextCollection, false);

        return max(0, (int) $secondsUntilNext);
    }

    /**
     * Get detailed collection status for monitoring
     */
    public function getCollectionStatus(): array
    {
        $latestSnapshot = $this->marketSnapshots()
            ->orderBy('collected_at', 'desc')
            ->first();

        return [
            'pair_symbol' => $this->pair_symbol,
            'is_active' => $this->is_active,
            'collection_interval_minutes' => $this->collection_interval_minutes,
            'last_collection_at' => $latestSnapshot?->collected_at,
            'minutes_since_last_collection' => $latestSnapshot ? now()->diffInMinutes($latestSnapshot->collected_at) : null,
            'is_collection_due' => $this->isCollectionDue(),
            'seconds_until_next_collection' => $this->timeUntilNextCollection(),
        ];
    }

    /**
     * Scope for active pairs only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific asset
     */
    public function scopeForAsset($query, string $asset)
    {
        return $query->where('asset', strtoupper($asset));
    }

    /**
     * Scope for specific fiat
     */
    public function scopeForFiat($query, string $fiat)
    {
        return $query->where('fiat', strtoupper($fiat));
    }

    /**
     * Get the effective volume ranges for data collection
     */
    public function getEffectiveVolumeRanges(): array
    {
        if (! $this->use_volume_sampling || empty($this->volume_ranges)) {
            return [];
        }

        return $this->volume_ranges;
    }

    /**
     * Get the effective sample volume for single-point collection
     */
    public function getEffectiveSampleVolume(): float
    {
        return $this->default_sample_volume ?? 500.00;
    }

    /**
     * Check if this pair should use volume sampling
     */
    public function shouldUseVolumeSampling(): bool
    {
        return $this->use_volume_sampling && ! empty($this->volume_ranges);
    }

    /**
     * Enable volume sampling for this trading pair
     */
    public function enableVolumeSampling(array $volumeRanges = [100, 500, 1000, 2500, 5000]): void
    {
        $this->update([
            'use_volume_sampling' => true,
            'volume_ranges' => $volumeRanges,
        ]);
    }

    /**
     * Disable volume sampling for this trading pair
     */
    public function disableVolumeSampling(): void
    {
        $this->update([
            'use_volume_sampling' => false,
            'volume_ranges' => null,
        ]);
    }
}
