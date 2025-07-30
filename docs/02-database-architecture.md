# Database Architecture - P2P Market Data Analysis Module

## Overview

The database architecture is designed specifically for cryptocurrency market data analysis with time-series optimization, statistical processing capabilities, and data quality management. The schema supports flexible trading pair configurations, comprehensive market data storage, and efficient analytical queries.

## Entity Relationship Diagram

```
┌─────────────────────────┐    ┌─────────────────────────────┐
│      TradingPair        │    │    P2PMarketSnapshot        │
│                         │    │                             │
│ • id                    │◄───┤• id                         │
│ • asset (e.g., 'USDT')  │    │ • trading_pair_id           │
│ • fiat (e.g., 'VES')    │    │ • trade_type ('BUY'/'SELL') │
│ • pair_symbol           │    │ • collected_at              │
│ • is_active             │    │ • raw_data (JSON)           │
│ • collection_config     │    │ • total_ads                 │
│ • min_trade_amount      │    │ • data_quality_score        │
│ • max_trade_amount      │    │ • collection_metadata       │
│ • collection_interval   │    │                             │
└─────────────────────────┘    └─────────────────────────────┘
                                            │
                                            ▼
                                 ┌─────────────────────┐
                                 │  Dynamic Processing │
                                 │                     │
                                 │  Statistical        │
                                 │  analysis performed │
                                 │  on-demand from     │
                                 │  raw_data field     │
                                 └─────────────────────┘
```

## Core Tables

### 1. trading_pairs

**Purpose**: Central configuration table for supported cryptocurrency trading pairs.

```sql
CREATE TABLE trading_pairs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asset VARCHAR(10) NOT NULL,                    -- e.g., 'USDT', 'BTC'
    fiat VARCHAR(10) NOT NULL,                     -- e.g., 'VES', 'USD'
    pair_symbol VARCHAR(20) NOT NULL UNIQUE,       -- e.g., 'USDT/VES'
    is_active BOOLEAN DEFAULT TRUE,
    collection_interval_minutes INT DEFAULT 5,
    collection_config JSON NULL,                   -- Additional API parameters
    min_trade_amount DECIMAL(20,8) NULL,
    max_trade_amount DECIMAL(20,8) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_asset_fiat (asset, fiat),
    INDEX idx_active_pairs (is_active)
);
```

**Key Features**:
- Supports any cryptocurrency pair combination
- Configurable collection intervals per pair
- JSON configuration for pair-specific settings
- Active/inactive status control

**Model Relationships**:
```php
// TradingPair.php
public function marketSnapshots(): HasMany
{
    return $this->hasMany(P2PMarketSnapshot::class);
}

public function recentSnapshots(int $hours = 24): HasMany
{
    return $this->marketSnapshots()
        ->where('collected_at', '>=', now()->subHours($hours))
        ->orderBy('collected_at', 'desc');
}
```

### 2. p2p_market_snapshots

**Purpose**: Stores raw P2P market data from Binance API with quality scoring and statistical processing capabilities.

```sql
CREATE TABLE p2p_market_snapshots (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trading_pair_id BIGINT UNSIGNED NOT NULL,
    trade_type ENUM('BUY', 'SELL') NOT NULL,
    collected_at TIMESTAMP NOT NULL,
    raw_data JSON NOT NULL,                        -- Complete API response
    total_ads INT DEFAULT 0,                       -- Number of ads in response
    data_quality_score DECIMAL(5,4) NULL,         -- Quality score (0-1)
    collection_metadata JSON NULL,                 -- API response time, etc.
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (trading_pair_id) REFERENCES trading_pairs(id) ON DELETE CASCADE,
    
    INDEX idx_snapshots_pair_type_time (trading_pair_id, trade_type, collected_at),
    INDEX idx_snapshots_collected_at (collected_at),
    INDEX idx_snapshots_quality (data_quality_score)
);
```

**Key Features**:
- Complete API response storage in JSON format
- Automatic data quality scoring (0.00 - 1.00)
- Trade type separation (BUY/SELL)
- Performance metrics (API response time)
- Collection metadata for debugging

**Key Model Methods**:
```php
// P2PMarketSnapshot.php
public function calculateQualityScore(): float         // Calculate data quality 0-1
public function getPriceStatistics(): array           // Extract price metrics
public function getMerchantStatistics(): array        // Extract merchant data
public function getSummary(): array                   // API response summary
public function getPriceMetrics(): array              // Backwards compatible metrics

// Scopes for filtering
public function scopeForTradeType(Builder $query, string $tradeType)
public function scopeHighQuality(Builder $query, float $threshold = 0.8)
public function scopeRecent(Builder $query, int $hours = 24)
public function scopeBetweenDates(Builder $query, Carbon $start, Carbon $end)
```

## Architecture Note: Dynamic Processing vs Pre-Aggregation

**Important**: The current system architecture uses **dynamic statistical processing** rather than pre-aggregating data into separate tables. All statistical analysis, outlier detection, and market metrics are calculated on-demand from the raw market data stored in the `raw_data` JSON field.

### Dynamic Processing Benefits:
- **Flexibility**: Statistical algorithms can be modified without database migrations
- **Real-time Analysis**: Fresh calculations for every request using latest algorithms
- **Storage Efficiency**: No duplicate data storage in multiple aggregation tables
- **Simplified Schema**: Only two core tables to maintain
- **Algorithm Evolution**: Easy to improve statistical methods without data migration

### Statistical Processing Capabilities:

**Price Analysis** (calculated dynamically from `raw_data`):
```php
// From P2PMarketSnapshot model
$priceStats = $snapshot->getPriceStatistics();
// Returns: best_price, avg_price, worst_price, median_price, 
//          total_volume, order_count, price_spread, volume_weighted_price

$merchantStats = $snapshot->getMerchantStatistics();
// Returns: unique_merchants, pro_merchant_count, avg_completion_rate,
//          avg_pay_time, avg_release_time
```

**Advanced Statistical Analysis** (performed by services):
- Multiple outlier detection methods (IQR, Z-Score, Modified Z-Score)
- Volume-weighted, reliability-weighted, and time-weighted averages
- Confidence intervals (90%, 95%, 99%)
- Linear regression and trend analysis
- Rolling volatility calculations
- Percentile analysis (P5, P10, P25, P50, P75, P90, P95)

**Data Quality Scoring** (real-time calculation):
```php
$qualityScore = $snapshot->calculateQualityScore();
// Factors: ad count, price spread, data freshness, completeness
// Returns: float between 0.0 and 1.0
```

## Performance Optimization

### Indexing Strategy

```sql
-- Primary composite index for time-series queries
CREATE INDEX idx_snapshots_pair_type_time ON p2p_market_snapshots 
    (trading_pair_id, trade_type, collected_at);

-- Time-based filtering index
CREATE INDEX idx_snapshots_collected_at ON p2p_market_snapshots 
    (collected_at);

-- Quality filtering index
CREATE INDEX idx_snapshots_quality ON p2p_market_snapshots 
    (data_quality_score);

-- Trading pair indexes
CREATE INDEX idx_asset_fiat ON trading_pairs (asset, fiat);
CREATE INDEX idx_active_pairs ON trading_pairs (is_active);
```

### Query Optimization

**Common Query Patterns**:
```sql
-- Recent high-quality snapshots
SELECT * FROM p2p_market_snapshots 
WHERE trading_pair_id = ? 
  AND trade_type = ? 
  AND collected_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
  AND data_quality_score >= 0.8
ORDER BY collected_at DESC;

-- Time-series analysis queries
SELECT raw_data, collected_at, data_quality_score
FROM p2p_market_snapshots 
WHERE trading_pair_id = ? 
  AND collected_at BETWEEN ? AND ?
ORDER BY collected_at ASC;

-- Latest snapshot per trade type
SELECT DISTINCT 
    trade_type,
    FIRST_VALUE(raw_data) OVER (
        PARTITION BY trade_type 
        ORDER BY collected_at DESC
    ) as latest_data
FROM p2p_market_snapshots
WHERE trading_pair_id = ?
  AND collected_at >= DATE_SUB(NOW(), INTERVAL 30 MINUTE);
```

## Data Quality Management

### Quality Scoring Algorithm

```php
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
    $prices = $ads->pluck('adv.price')->map(fn($p) => (float) $p);
    if ($prices->count() > 1) {
        $spread = ($prices->max() - $prices->min()) / $prices->avg();
        if ($spread > 0.1) {
            $factors['high_spread'] = -0.2;
        } elseif ($spread > 0.05) {
            $factors['medium_spread'] = -0.1;
        }
    }
    
    // Factor 3: Data freshness
    $collectionAge = now()->diffInMinutes($this->collected_at);
    if ($collectionAge > 10) {
        $factors['stale_data'] = -0.1;
    }
    
    // Factor 4: Data completeness
    $incompleteAds = $ads->filter(function ($ad) {
        return empty($ad['adv']['price']) || 
               empty($ad['adv']['surplusAmount']) ||
               empty($ad['advertiser']['nickName']);
    })->count();
    
    if ($incompleteAds > 0) {
        $factors['incomplete_data'] = -($incompleteAds / $adsCount) * 0.3;
    }
    
    return max(0.0, min(1.0, $score + array_sum($factors)));
}
```

### Data Retention Policies

```php
// Automatic cleanup configuration for simplified schema
'data_retention' => [
    'market_snapshots' => 30,   // days - raw P2P market data
    'trading_pairs' => null,    // permanent - configuration data
    'archive_before_delete' => true,
    'cleanup_schedule' => 'daily',
]
```

## Model Relationships

### Simplified Relationship Map

```php
// TradingPair relationships
public function marketSnapshots(): HasMany
public function recentSnapshots(int $hours = 24): HasMany
public function latestSnapshot(string $tradeType = 'BUY'): ?P2PMarketSnapshot

// P2PMarketSnapshot relationships
public function tradingPair(): BelongsTo
```

### Advanced Query Scopes

```php
// TradingPair scopes
public function scopeActive($query)              // Active pairs only
public function scopeForAsset($query, $asset)    // Filter by asset
public function scopeForFiat($query, $fiat)      // Filter by fiat

// P2PMarketSnapshot scopes
public function scopeForTradeType($query, $tradeType)           // BUY or SELL
public function scopeForTradingPair($query, $tradingPairId)     // Specific pair
public function scopeRecent($query, $hours = 24)               // Recent data
public function scopeHighQuality($query, $threshold = 0.8)     // Quality filter
public function scopeBetweenDates($query, $start, $end)        // Date range
```

### Business Logic Methods

```php
// TradingPair business methods
public function isCollectionDue(): bool          // Check if data collection needed
public static function needingCollection()       // Get pairs needing collection
public static function createPair($asset, $fiat) // Factory method

// P2PMarketSnapshot analysis methods
public function getPriceStatistics(): array      // Price metrics extraction
public function getMerchantStatistics(): array   // Merchant performance data
public function getPriceMetrics(): array         // Backwards compatible metrics
public function getSummary(): array              // API response summary
public function updateQualityScore(): void       // Recalculate quality
public function isRecent(int $minutes = 30): bool // Freshness check
```

This simplified database architecture provides a robust foundation for cryptocurrency market data analysis with:

- **Streamlined Schema**: Only 2 core tables with clear responsibilities
- **Dynamic Processing**: Statistical analysis calculated on-demand for maximum flexibility
- **Optimized Performance**: Time-series indexes and efficient query patterns
- **Data Quality Management**: Real-time quality scoring and validation
- **Flexible Analysis**: Easily adaptable statistical algorithms without schema changes
- **Efficient Storage**: Raw data preservation with dynamic metric calculation

The architecture prioritizes flexibility and maintainability over pre-aggregation, enabling rapid algorithm improvements and reducing database complexity while maintaining high performance for analytical queries.