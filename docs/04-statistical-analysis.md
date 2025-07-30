# Statistical Analysis - P2P Market Data Analysis Module

## Overview

The Statistical Analysis component provides advanced mathematical algorithms for cryptocurrency market data analysis, featuring multiple outlier detection methods, sophisticated averaging techniques, and comprehensive market volatility assessment. The system is designed to handle real-world financial data with noise, manipulation attempts, and varying data quality.

## Core Statistical Algorithms

### 1. Outlier Detection Methods

#### 1.1 Interquartile Range (IQR) Method

**Algorithm**: 
- Calculate Q1 (25th percentile) and Q3 (75th percentile)
- Compute IQR = Q3 - Q1
- Define outliers as values outside [Q1 - 1.5×IQR, Q3 + 1.5×IQR]

**Implementation**:
```php
private function detectOutliersIQR(array $prices): array
{
    sort($prices);
    $count = count($prices);
    
    $q1Index = floor($count * 0.25);
    $q3Index = floor($count * 0.75);
    
    $q1 = $prices[$q1Index];
    $q3 = $prices[$q3Index];
    $iqr = $q3 - $q1;
    
    $lowerBound = $q1 - (1.5 * $iqr);
    $upperBound = $q3 + (1.5 * $iqr);
    
    $outliers = [];
    foreach ($prices as $index => $price) {
        if ($price < $lowerBound || $price > $upperBound) {
            $outliers[] = [
                'index' => $index,
                'value' => $price,
                'type' => $price < $lowerBound ? 'lower' : 'upper'
            ];
        }
    }
    
    return $outliers;
}
```

**Best For**: 
- Skewed distributions
- Financial data with natural bounds
- Robust against extreme outliers

#### 1.2 Z-Score Method

**Algorithm**:
- Calculate mean (μ) and standard deviation (σ)
- Z-score = (x - μ) / σ
- Outliers: |Z-score| > threshold (typically 2.5 or 3.0)

**Implementation**:
```php
private function detectOutliersZScore(array $prices, float $threshold = 2.5): array
{
    $mean = array_sum($prices) / count($prices);
    $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $prices)) / count($prices);
    $stdDev = sqrt($variance);
    
    $outliers = [];
    foreach ($prices as $index => $price) {
        $zScore = ($price - $mean) / $stdDev;
        if (abs($zScore) > $threshold) {
            $outliers[] = [
                'index' => $index,
                'value' => $price,
                'z_score' => $zScore,
                'deviation' => abs($zScore) * $stdDev
            ];
        }
    }
    
    return $outliers;
}
```

**Best For**:
- Normally distributed data
- When you need standardized deviation measures
- Academic/research applications

#### 1.3 Modified Z-Score Method

**Algorithm**:
- Uses median and median absolute deviation (MAD)
- Modified Z-score = 0.6745 × (x - median) / MAD
- More robust than standard Z-score

**Implementation**:
```php
private function detectOutliersModifiedZScore(array $prices, float $threshold = 3.5): array
{
    sort($prices);
    $median = $this->calculateMedian($prices);
    
    // Calculate MAD (Median Absolute Deviation)
    $deviations = array_map(fn($x) => abs($x - $median), $prices);
    sort($deviations);
    $mad = $this->calculateMedian($deviations);
    
    $outliers = [];
    foreach ($prices as $index => $price) {
        $modifiedZScore = 0.6745 * ($price - $median) / $mad;
        if (abs($modifiedZScore) > $threshold) {
            $outliers[] = [
                'index' => $index,
                'value' => $price,
                'modified_z_score' => $modifiedZScore,
                'mad_deviation' => abs($price - $median)
            ];
        }
    }
    
    return $outliers;
}
```

**Best For**:
- Non-normal distributions
- Presence of multiple outliers
- High-noise financial data

### 2. Advanced Averaging Methods

#### 2.1 Volume-Weighted Average Price (VWAP)

**Purpose**: Prevent manipulation by low-volume fake orders

**Algorithm**:
```php
private function calculateVWAP(array $orders): float
{
    $totalValue = 0;
    $totalVolume = 0;
    
    foreach ($orders as $order) {
        $volume = $order['available_amount'];
        $price = $order['price'];
        
        $totalValue += $price * $volume;
        $totalVolume += $volume;
    }
    
    return $totalVolume > 0 ? $totalValue / $totalVolume : 0;
}
```

#### 2.2 Reliability-Weighted Average

**Purpose**: Give more weight to orders from trusted merchants

**Algorithm**:
```php
private function calculateReliabilityWeightedAverage(array $orders): float
{
    $totalWeightedPrice = 0;
    $totalWeight = 0;
    
    foreach ($orders as $order) {
        $reliability = $order['merchant_completion_rate'] / 100;
        $orderCount = $order['merchant_orders_count'];
        
        // Combine reliability and experience
        $weight = $reliability * log(1 + $orderCount);
        
        $totalWeightedPrice += $order['price'] * $weight;
        $totalWeight += $weight;
    }
    
    return $totalWeight > 0 ? $totalWeightedPrice / $totalWeight : 0;
}
```

#### 2.3 Time-Weighted Averages

**Exponential Decay**:
```php
private function calculateExponentialWeightedAverage(array $prices, float $alpha = 0.1): float
{
    if (empty($prices)) return 0;
    
    $ewa = $prices[0];
    for ($i = 1; $i < count($prices); $i++) {
        $ewa = $alpha * $prices[$i] + (1 - $alpha) * $ewa;
    }
    
    return $ewa;
}
```

**Linear Decay**:
```php
private function calculateLinearDecayAverage(array $prices): float
{
    $totalWeightedPrice = 0;
    $totalWeight = 0;
    $count = count($prices);
    
    foreach ($prices as $index => $price) {
        $weight = ($index + 1) / $count; // Recent data gets higher weight
        $totalWeightedPrice += $price * $weight;
        $totalWeight += $weight;
    }
    
    return $totalWeight > 0 ? $totalWeightedPrice / $totalWeight : 0;
}
```

### 3. Statistical Measures

#### 3.1 Basic Statistics

```php
private function calculateBasicStatistics(array $prices): array
{
    if (empty($prices)) return [];
    
    $count = count($prices);
    $mean = array_sum($prices) / $count;
    $median = $this->calculateMedian($prices);
    $mode = $this->calculateMode($prices);
    
    // Variance and Standard Deviation
    $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $prices)) / $count;
    $stdDev = sqrt($variance);
    
    // Range and other measures
    $min = min($prices);
    $max = max($prices);
    $range = $max - $min;
    
    // Coefficient of Variation
    $cv = $mean != 0 ? $stdDev / $mean : 0;
    
    return [
        'count' => $count,
        'mean' => $mean,
        'median' => $median,
        'mode' => $mode,
        'standard_deviation' => $stdDev,
        'variance' => $variance,
        'min' => $min,
        'max' => $max,
        'range' => $range,
        'coefficient_of_variation' => $cv
    ];
}
```

#### 3.2 Distribution Analysis

**Skewness** (measure of asymmetry):
```php
private function calculateSkewness(array $prices): float
{
    $count = count($prices);
    if ($count < 3) return 0;
    
    $mean = array_sum($prices) / $count;
    $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $prices)) / $count;
    $stdDev = sqrt($variance);
    
    if ($stdDev == 0) return 0;
    
    $skewness = 0;
    foreach ($prices as $price) {
        $skewness += pow(($price - $mean) / $stdDev, 3);
    }
    
    return $skewness / $count;
}
```

**Kurtosis** (measure of tail heaviness):
```php
private function calculateKurtosis(array $prices): float
{
    $count = count($prices);
    if ($count < 4) return 0;
    
    $mean = array_sum($prices) / $count;
    $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $prices)) / $count;
    $stdDev = sqrt($variance);
    
    if ($stdDev == 0) return 0;
    
    $kurtosis = 0;
    foreach ($prices as $price) {
        $kurtosis += pow(($price - $mean) / $stdDev, 4);
    }
    
    return ($kurtosis / $count) - 3; // Excess kurtosis
}
```

#### 3.3 Confidence Intervals

```php
private function calculateConfidenceInterval(
    array $prices, 
    float $confidenceLevel = 0.95
): array {
    $count = count($prices);
    if ($count < 2) return [];
    
    $mean = array_sum($prices) / $count;
    $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $prices)) / ($count - 1);
    $standardError = sqrt($variance / $count);
    
    // Critical values for common confidence levels
    $criticalValues = [
        0.90 => 1.645,
        0.95 => 1.96,
        0.99 => 2.576
    ];
    
    $criticalValue = $criticalValues[$confidenceLevel] ?? 1.96;
    $marginOfError = $criticalValue * $standardError;
    
    return [
        'confidence_level' => $confidenceLevel,
        'mean' => $mean,
        'standard_error' => $standardError,
        'margin_of_error' => $marginOfError,
        'lower_bound' => $mean - $marginOfError,
        'upper_bound' => $mean + $marginOfError,
        'sample_size' => $count
    ];
}
```

### 4. Percentile Analysis

```php
private function calculatePercentiles(array $prices): array
{
    sort($prices);
    $count = count($prices);
    
    $percentiles = [5, 10, 25, 50, 75, 90, 95];
    $results = [];
    
    foreach ($percentiles as $p) {
        $index = ($p / 100) * ($count - 1);
        
        if (is_int($index)) {
            $value = $prices[$index];
        } else {
            $lowerIndex = floor($index);
            $upperIndex = ceil($index);
            $fraction = $index - $lowerIndex;
            
            $value = $prices[$lowerIndex] * (1 - $fraction) + 
                    $prices[$upperIndex] * $fraction;
        }
        
        $results["P{$p}"] = $value;
    }
    
    return $results;
}
```

### 5. Trend Analysis

#### 5.1 Linear Regression

```php
private function calculateLinearRegression(array $prices): array
{
    $n = count($prices);
    if ($n < 2) return [];
    
    // Create time series (x values)
    $x = range(0, $n - 1);
    $y = $prices;
    
    // Calculate means
    $xMean = array_sum($x) / $n;
    $yMean = array_sum($y) / $n;
    
    // Calculate slope (β1) and intercept (β0)
    $numerator = 0;
    $denominator = 0;
    
    for ($i = 0; $i < $n; $i++) {
        $numerator += ($x[$i] - $xMean) * ($y[$i] - $yMean);
        $denominator += pow($x[$i] - $xMean, 2);
    }
    
    $slope = $denominator != 0 ? $numerator / $denominator : 0;
    $intercept = $yMean - $slope * $xMean;
    
    // Calculate R-squared
    $ssTotal = array_sum(array_map(fn($yi) => pow($yi - $yMean, 2), $y));
    $ssResidual = 0;
    
    for ($i = 0; $i < $n; $i++) {
        $predicted = $slope * $x[$i] + $intercept;
        $ssResidual += pow($y[$i] - $predicted, 2);
    }
    
    $rSquared = $ssTotal != 0 ? 1 - ($ssResidual / $ssTotal) : 0;
    
    return [
        'slope' => $slope,
        'intercept' => $intercept,
        'r_squared' => $rSquared,
        'trend_direction' => $slope > 0 ? 'upward' : ($slope < 0 ? 'downward' : 'flat'),
        'trend_strength' => $this->classifyTrendStrength($rSquared),
        'price_change_rate' => $slope
    ];
}

private function classifyTrendStrength(float $rSquared): string
{
    if ($rSquared >= 0.8) return 'very_strong';
    if ($rSquared >= 0.6) return 'strong';
    if ($rSquared >= 0.4) return 'moderate';
    if ($rSquared >= 0.2) return 'weak';
    return 'very_weak';
}
```

### 6. Volatility Analysis

#### 6.1 Rolling Volatility

```php
private function calculateRollingVolatility(array $prices, int $window): array
{
    $volatilities = [];
    
    for ($i = $window - 1; $i < count($prices); $i++) {
        $windowPrices = array_slice($prices, $i - $window + 1, $window);
        
        // Calculate returns
        $returns = [];
        for ($j = 1; $j < count($windowPrices); $j++) {
            $returns[] = ($windowPrices[$j] - $windowPrices[$j-1]) / $windowPrices[$j-1];
        }
        
        // Calculate volatility (standard deviation of returns)
        if (count($returns) > 1) {
            $mean = array_sum($returns) / count($returns);
            $variance = array_sum(array_map(fn($r) => pow($r - $mean, 2), $returns)) / count($returns);
            $volatilities[] = sqrt($variance);
        }
    }
    
    return $volatilities;
}
```

#### 6.2 Volatility Classification

```php
private function classifyVolatility(float $volatility): string
{
    if ($volatility < 0.01) return 'very_low';
    if ($volatility < 0.02) return 'low';
    if ($volatility < 0.05) return 'moderate';
    if ($volatility < 0.10) return 'high';
    return 'very_high';
}
```

### 7. Market Quality Assessment

#### 7.1 Data Quality Scoring

```php
private function calculateDataQualityScore(array $orderData): float
{
    $score = 1.0;
    $penalties = [];
    
    // Completeness check (40% weight)
    $completeness = $this->assessDataCompleteness($orderData);
    $penalties['completeness'] = (1 - $completeness) * 0.4;
    
    // Price consistency (30% weight)
    $consistency = $this->assessPriceConsistency($orderData);
    $penalties['consistency'] = (1 - $consistency) * 0.3;
    
    // Merchant reliability (20% weight)
    $reliability = $this->assessMerchantReliability($orderData);
    $penalties['reliability'] = (1 - $reliability) * 0.2;
    
    // Temporal relevance (10% weight)
    $temporal = $this->assessTemporalRelevance($orderData);
    $penalties['temporal'] = (1 - $temporal) * 0.1;
    
    $totalPenalty = array_sum($penalties);
    return max(0, $score - $totalPenalty);
}

private function assessDataCompleteness(array $orderData): float
{
    $requiredFields = ['price', 'available_amount', 'min_order_amount', 'merchant_name'];
    $totalFields = count($requiredFields) * count($orderData);
    $presentFields = 0;
    
    foreach ($orderData as $order) {
        foreach ($requiredFields as $field) {
            if (isset($order[$field]) && !empty($order[$field])) {
                $presentFields++;
            }
        }
    }
    
    return $totalFields > 0 ? $presentFields / $totalFields : 0;
}
```

### 8. Market Manipulation Detection

#### 8.1 Price Anomaly Detection

```php
private function detectPriceAnomalies(array $prices): array
{
    $anomalies = [];
    
    // Detect sudden price jumps
    for ($i = 1; $i < count($prices); $i++) {
        $priceChange = abs($prices[$i] - $prices[$i-1]) / $prices[$i-1];
        
        if ($priceChange > 0.05) { // 5% threshold
            $anomalies[] = [
                'type' => 'price_jump',
                'index' => $i,
                'current_price' => $prices[$i],
                'previous_price' => $prices[$i-1],
                'change_percentage' => $priceChange * 100,
                'severity' => $this->classifyAnomalySeverity($priceChange)
            ];
        }
    }
    
    return $anomalies;
}

private function classifyAnomalySeverity(float $change): string
{
    if ($change > 0.20) return 'critical';
    if ($change > 0.10) return 'high';
    if ($change > 0.05) return 'medium';
    return 'low';
}
```

### 9. Performance Optimization

#### 9.1 Streaming Statistics

For large datasets, implement streaming algorithms:

```php
class StreamingStatistics
{
    private int $count = 0;
    private float $mean = 0;
    private float $m2 = 0; // For variance calculation
    
    public function update(float $value): void
    {
        $this->count++;
        $delta = $value - $this->mean;
        $this->mean += $delta / $this->count;
        $delta2 = $value - $this->mean;
        $this->m2 += $delta * $delta2;
    }
    
    public function getMean(): float
    {
        return $this->mean;
    }
    
    public function getVariance(): float
    {
        return $this->count > 1 ? $this->m2 / ($this->count - 1) : 0;
    }
    
    public function getStandardDeviation(): float
    {
        return sqrt($this->getVariance());
    }
}
```

## Algorithm Selection Guidelines

### When to Use Each Outlier Detection Method

| Method | Best For | Pros | Cons |
|--------|----------|------|------|
| **IQR** | Skewed financial data | Robust, interpretable | Less sensitive to mild outliers |
| **Z-Score** | Normal distributions | Simple, standardized | Assumes normality |
| **Modified Z-Score** | Non-normal data | Very robust | More complex calculation |

### Averaging Method Selection

| Method | Use Case | Advantages |
|--------|----------|------------|
| **VWAP** | Volume matters | Prevents manipulation |
| **Reliability-Weighted** | Merchant trust important | Quality-based weighting |
| **Time-Weighted** | Recent data priority | Trend-following |

### Statistical Significance

All statistical measures include significance testing and confidence intervals to ensure reliable results for trading decisions.

This comprehensive statistical analysis framework provides the mathematical foundation for accurate cryptocurrency market analysis while being robust against manipulation and data quality issues.