<?php

namespace App\Services;

class StatisticalAnalysisService
{
    /**
     * Configuration for statistical analysis
     */
    private array $config;

    public function __construct()
    {
        $this->config = [
            'outlier_detection' => [
                'iqr_multiplier' => 1.5,
                'zscore_threshold' => 2.5,
                'modified_zscore_threshold' => 3.5,
                'default_method' => 'iqr', // 'iqr', 'zscore', 'modified_zscore'
            ],
            'confidence_intervals' => [
                'default_level' => 0.95, // 95% confidence interval
                'supported_levels' => [0.90, 0.95, 0.99],
            ],
            'time_weighting' => [
                'decay_factor' => 0.1, // Higher values give more weight to recent data
                'max_age_hours' => 24,
            ],
            'volatility' => [
                'rolling_periods' => [5, 10, 20, 50], // periods for rolling volatility
            ],
        ];
    }

    /**
     * Comprehensive statistical analysis of price data
     */
    public function analyzeMarketData(array $marketData, array $options = []): array
    {
        $priceData = $this->extractPriceData($marketData);

        if (empty($priceData)) {
            return $this->getEmptyAnalysis();
        }

        $outlierMethod = $options['outlier_method'] ?? $this->config['outlier_detection']['default_method'];
        $confidenceLevel = $options['confidence_level'] ?? $this->config['confidence_intervals']['default_level'];

        // Detect and filter outliers
        $outliers = $this->detectOutliers($priceData, $outlierMethod);
        $cleanData = $this->filterOutliers($priceData, $outliers);

        return [
            'raw_statistics' => $this->calculateBasicStatistics($priceData),
            'cleaned_statistics' => $this->calculateBasicStatistics($cleanData),
            'outlier_analysis' => [
                'method_used' => $outlierMethod,
                'outliers_detected' => count($outliers),
                'outlier_percentage' => count($priceData) > 0 ? round((count($outliers) / count($priceData)) * 100, 2) : 0,
                'outlier_values' => $outliers,
                'outlier_indices' => array_keys($outliers),
            ],
            'weighted_averages' => $this->calculateWeightedAverages($marketData),
            'time_weighted_averages' => $this->calculateTimeWeightedAverages($marketData),
            'confidence_intervals' => $this->calculateConfidenceIntervals($cleanData, $confidenceLevel),
            'percentile_analysis' => $this->calculatePercentiles($cleanData),
            'trend_analysis' => $this->calculateTrendAnalysis($cleanData),
            'volatility_analysis' => $this->calculateVolatilityAnalysis($cleanData),
            'statistical_tests' => $this->performStatisticalTests($priceData, $cleanData),
            'quality_metrics' => $this->calculateDataQualityMetrics($priceData, $cleanData, $outliers),
        ];
    }

    /**
     * Extract price data from market data array
     */
    private function extractPriceData(array $marketData): array
    {
        if (empty($marketData['data'])) {
            return [];
        }

        return collect($marketData['data'])
            ->map(function ($item) {
                return [
                    'price' => (float) $item['adv']['price'],
                    'volume' => (float) ($item['adv']['tradableQuantity'] ?? 0),
                    'min_amount' => (float) ($item['adv']['minSingleTransAmount'] ?? 0),
                    'max_amount' => (float) ($item['adv']['maxSingleTransAmount'] ?? 0),
                    'timestamp' => now()->timestamp,
                    'merchant_trades' => (int) ($item['advertiser']['monthOrderCount'] ?? 0),
                    'completion_rate' => (float) ($item['advertiser']['monthFinishRate'] ?? 0),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Detect outliers using various statistical methods
     */
    public function detectOutliers(array $priceData, string $method = 'iqr'): array
    {
        if (empty($priceData)) {
            return [];
        }

        $prices = array_column($priceData, 'price');

        switch ($method) {
            case 'iqr':
                return $this->detectOutliersIQR($prices);
            case 'zscore':
                return $this->detectOutliersZScore($prices);
            case 'modified_zscore':
                return $this->detectOutliersModifiedZScore($prices);
            default:
                throw new \InvalidArgumentException("Unsupported outlier detection method: {$method}");
        }
    }

    /**
     * Detect outliers using Interquartile Range (IQR) method
     */
    private function detectOutliersIQR(array $prices): array
    {
        $sorted = $prices;
        sort($sorted);
        $count = count($sorted);

        if ($count < 4) {
            return []; // Need at least 4 data points for IQR
        }

        $q1Index = (int) floor($count * 0.25);
        $q3Index = (int) floor($count * 0.75);

        $q1 = $sorted[$q1Index];
        $q3 = $sorted[$q3Index];
        $iqr = $q3 - $q1;

        $lowerBound = $q1 - ($this->config['outlier_detection']['iqr_multiplier'] * $iqr);
        $upperBound = $q3 + ($this->config['outlier_detection']['iqr_multiplier'] * $iqr);

        $outliers = [];
        foreach ($prices as $index => $price) {
            if ($price < $lowerBound || $price > $upperBound) {
                $outliers[$index] = $price;
            }
        }

        return $outliers;
    }

    /**
     * Detect outliers using Z-Score method
     */
    private function detectOutliersZScore(array $prices): array
    {
        $mean = array_sum($prices) / count($prices);
        $variance = array_sum(array_map(fn ($x) => pow($x - $mean, 2), $prices)) / count($prices);
        $stdDev = sqrt($variance);

        if ($stdDev == 0) {
            return []; // No variation, no outliers
        }

        $threshold = $this->config['outlier_detection']['zscore_threshold'];
        $outliers = [];

        foreach ($prices as $index => $price) {
            $zScore = abs(($price - $mean) / $stdDev);
            if ($zScore > $threshold) {
                $outliers[$index] = $price;
            }
        }

        return $outliers;
    }

    /**
     * Detect outliers using Modified Z-Score method (more robust)
     */
    private function detectOutliersModifiedZScore(array $prices): array
    {
        $median = $this->calculateMedian($prices);
        $deviations = array_map(fn ($x) => abs($x - $median), $prices);
        $mad = $this->calculateMedian($deviations); // Median Absolute Deviation

        if ($mad == 0) {
            return []; // No variation, no outliers
        }

        $threshold = $this->config['outlier_detection']['modified_zscore_threshold'];
        $outliers = [];

        foreach ($prices as $index => $price) {
            $modifiedZScore = 0.6745 * abs($price - $median) / $mad;
            if ($modifiedZScore > $threshold) {
                $outliers[$index] = $price;
            }
        }

        return $outliers;
    }

    /**
     * Filter out outliers from price data
     */
    private function filterOutliers(array $priceData, array $outliers): array
    {
        return array_values(array_filter($priceData, function ($item, $index) use ($outliers) {
            return ! isset($outliers[$index]);
        }, ARRAY_FILTER_USE_BOTH));
    }

    /**
     * Calculate basic statistical measures
     */
    private function calculateBasicStatistics(array $priceData): array
    {
        if (empty($priceData)) {
            return $this->getEmptyStatistics();
        }

        $prices = array_column($priceData, 'price');
        $count = count($prices);

        $mean = array_sum($prices) / $count;
        $median = $this->calculateMedian($prices);
        $mode = $this->calculateMode($prices);
        $variance = array_sum(array_map(fn ($x) => pow($x - $mean, 2), $prices)) / $count;
        $stdDev = sqrt($variance);
        $min = min($prices);
        $max = max($prices);
        $range = $max - $min;

        return [
            'count' => $count,
            'mean' => round($mean, 8),
            'median' => round($median, 8),
            'mode' => $mode ? round($mode, 8) : null,
            'standard_deviation' => round($stdDev, 8),
            'variance' => round($variance, 8),
            'min' => round($min, 8),
            'max' => round($max, 8),
            'range' => round($range, 8),
            'coefficient_of_variation' => $mean != 0 ? round(($stdDev / $mean) * 100, 4) : 0,
            'skewness' => $this->calculateSkewness($prices, $mean, $stdDev),
            'kurtosis' => $this->calculateKurtosis($prices, $mean, $stdDev),
        ];
    }

    /**
     * Calculate weighted averages based on volume and other factors
     */
    private function calculateWeightedAverages(array $marketData): array
    {
        $priceData = $this->extractPriceData($marketData);

        if (empty($priceData)) {
            return [];
        }

        return [
            'volume_weighted' => $this->calculateVolumeWeightedAverage($priceData),
            'trade_count_weighted' => $this->calculateTradeCountWeightedAverage($priceData),
            'reliability_weighted' => $this->calculateReliabilityWeightedAverage($priceData),
            'amount_weighted' => $this->calculateAmountWeightedAverage($priceData),
        ];
    }

    /**
     * Calculate volume-weighted average price (VWAP)
     */
    private function calculateVolumeWeightedAverage(array $priceData): float
    {
        $totalVolume = 0;
        $weightedSum = 0;

        foreach ($priceData as $data) {
            $volume = $data['volume'] > 0 ? $data['volume'] : 1; // Use 1 as minimum weight
            $totalVolume += $volume;
            $weightedSum += $data['price'] * $volume;
        }

        return $totalVolume > 0 ? round($weightedSum / $totalVolume, 8) : 0;
    }

    /**
     * Calculate trade count weighted average
     */
    private function calculateTradeCountWeightedAverage(array $priceData): float
    {
        $totalTrades = 0;
        $weightedSum = 0;

        foreach ($priceData as $data) {
            $trades = max($data['merchant_trades'], 1); // Minimum weight of 1
            $totalTrades += $trades;
            $weightedSum += $data['price'] * $trades;
        }

        return $totalTrades > 0 ? round($weightedSum / $totalTrades, 8) : 0;
    }

    /**
     * Calculate reliability weighted average based on completion rate
     */
    private function calculateReliabilityWeightedAverage(array $priceData): float
    {
        $totalWeight = 0;
        $weightedSum = 0;

        foreach ($priceData as $data) {
            $weight = max($data['completion_rate'] / 100, 0.1); // Minimum weight of 0.1
            $totalWeight += $weight;
            $weightedSum += $data['price'] * $weight;
        }

        return $totalWeight > 0 ? round($weightedSum / $totalWeight, 8) : 0;
    }

    /**
     * Calculate amount weighted average based on trading limits
     */
    private function calculateAmountWeightedAverage(array $priceData): float
    {
        $totalWeight = 0;
        $weightedSum = 0;

        foreach ($priceData as $data) {
            $weight = max($data['max_amount'] - $data['min_amount'], 1); // Range as weight
            $totalWeight += $weight;
            $weightedSum += $data['price'] * $weight;
        }

        return $totalWeight > 0 ? round($weightedSum / $totalWeight, 8) : 0;
    }

    /**
     * Calculate time-weighted averages with exponential decay
     */
    private function calculateTimeWeightedAverages(array $marketData): array
    {
        $priceData = $this->extractPriceData($marketData);

        if (empty($priceData)) {
            return [];
        }

        $currentTime = now()->timestamp;
        $decayFactor = $this->config['time_weighting']['decay_factor'];
        $maxAgeHours = $this->config['time_weighting']['max_age_hours'];

        return [
            'exponential_weighted' => $this->calculateExponentialWeightedAverage($priceData, $currentTime, $decayFactor),
            'linear_decay_weighted' => $this->calculateLinearDecayWeightedAverage($priceData, $currentTime, $maxAgeHours),
            'recent_emphasis_weighted' => $this->calculateRecentEmphasisWeightedAverage($priceData, $currentTime),
        ];
    }

    /**
     * Calculate exponential weighted average
     */
    private function calculateExponentialWeightedAverage(array $priceData, int $currentTime, float $decayFactor): float
    {
        $totalWeight = 0;
        $weightedSum = 0;

        foreach ($priceData as $data) {
            $ageHours = ($currentTime - $data['timestamp']) / 3600;
            $weight = exp(-$decayFactor * $ageHours);
            $totalWeight += $weight;
            $weightedSum += $data['price'] * $weight;
        }

        return $totalWeight > 0 ? round($weightedSum / $totalWeight, 8) : 0;
    }

    /**
     * Calculate linear decay weighted average
     */
    private function calculateLinearDecayWeightedAverage(array $priceData, int $currentTime, int $maxAgeHours): float
    {
        $totalWeight = 0;
        $weightedSum = 0;

        foreach ($priceData as $data) {
            $ageHours = ($currentTime - $data['timestamp']) / 3600;
            $weight = max(1 - ($ageHours / $maxAgeHours), 0.1); // Minimum weight of 0.1
            $totalWeight += $weight;
            $weightedSum += $data['price'] * $weight;
        }

        return $totalWeight > 0 ? round($weightedSum / $totalWeight, 8) : 0;
    }

    /**
     * Calculate recent emphasis weighted average
     */
    private function calculateRecentEmphasisWeightedAverage(array $priceData, int $currentTime): float
    {
        $totalWeight = 0;
        $weightedSum = 0;

        // Sort by timestamp, most recent first
        usort($priceData, fn ($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        foreach ($priceData as $index => $data) {
            $weight = 1 / (1 + $index); // Higher weight for more recent data
            $totalWeight += $weight;
            $weightedSum += $data['price'] * $weight;
        }

        return $totalWeight > 0 ? round($weightedSum / $totalWeight, 8) : 0;
    }

    /**
     * Calculate confidence intervals
     */
    private function calculateConfidenceIntervals(array $priceData, float $confidenceLevel): array
    {
        if (empty($priceData) || count($priceData) < 2) {
            return [];
        }

        $prices = array_column($priceData, 'price');
        $count = count($prices);
        $mean = array_sum($prices) / $count;
        $stdDev = sqrt(array_sum(array_map(fn ($x) => pow($x - $mean, 2), $prices)) / ($count - 1));

        // For large samples (n > 30), use normal distribution
        // For small samples, this is an approximation (should use t-distribution)
        $zScore = $this->getZScoreForConfidenceLevel($confidenceLevel);
        $marginOfError = $zScore * ($stdDev / sqrt($count));

        return [
            'confidence_level' => $confidenceLevel,
            'mean' => round($mean, 8),
            'margin_of_error' => round($marginOfError, 8),
            'lower_bound' => round($mean - $marginOfError, 8),
            'upper_bound' => round($mean + $marginOfError, 8),
            'sample_size' => $count,
            'standard_error' => round($stdDev / sqrt($count), 8),
        ];
    }

    /**
     * Calculate percentile analysis
     */
    private function calculatePercentiles(array $priceData): array
    {
        if (empty($priceData)) {
            return [];
        }

        $prices = array_column($priceData, 'price');
        sort($prices);

        $percentiles = [5, 10, 25, 50, 75, 90, 95];
        $result = [];

        foreach ($percentiles as $p) {
            $result["P{$p}"] = round($this->calculatePercentile($prices, $p), 8);
        }

        return $result;
    }

    /**
     * Calculate trend analysis
     */
    private function calculateTrendAnalysis(array $priceData): array
    {
        if (count($priceData) < 3) {
            return ['trend' => 'insufficient_data'];
        }

        $prices = array_column($priceData, 'price');
        $count = count($prices);

        // Calculate linear regression slope
        $xSum = array_sum(range(0, $count - 1));
        $ySum = array_sum($prices);
        $xySum = 0;
        $x2Sum = 0;

        for ($i = 0; $i < $count; $i++) {
            $xySum += $i * $prices[$i];
            $x2Sum += $i * $i;
        }

        $slope = ($count * $xySum - $xSum * $ySum) / ($count * $x2Sum - $xSum * $xSum);
        $intercept = ($ySum - $slope * $xSum) / $count;

        // Calculate R-squared
        $yMean = $ySum / $count;
        $ssTotal = array_sum(array_map(fn ($y) => pow($y - $yMean, 2), $prices));
        $ssRes = 0;

        for ($i = 0; $i < $count; $i++) {
            $predicted = $slope * $i + $intercept;
            $ssRes += pow($prices[$i] - $predicted, 2);
        }

        $rSquared = $ssTotal > 0 ? 1 - ($ssRes / $ssTotal) : 0;

        return [
            'slope' => round($slope, 8),
            'intercept' => round($intercept, 8),
            'r_squared' => round($rSquared, 6),
            'trend_direction' => $slope > 0.001 ? 'upward' : ($slope < -0.001 ? 'downward' : 'stable'),
            'trend_strength' => $this->getTrendStrength($rSquared),
            'price_change_rate' => round($slope, 8),
        ];
    }

    /**
     * Calculate volatility analysis
     */
    private function calculateVolatilityAnalysis(array $priceData): array
    {
        if (empty($priceData)) {
            return [];
        }

        $prices = array_column($priceData, 'price');
        $count = count($prices);

        if ($count < 2) {
            return ['volatility' => 0];
        }

        $mean = array_sum($prices) / $count;
        $variance = array_sum(array_map(fn ($x) => pow($x - $mean, 2), $prices)) / ($count - 1);
        $stdDev = sqrt($variance);

        $result = [
            'absolute_volatility' => round($stdDev, 8),
            'relative_volatility' => $mean != 0 ? round(($stdDev / $mean) * 100, 4) : 0,
            'volatility_classification' => $this->classifyVolatility($stdDev, $mean),
        ];

        // Calculate rolling volatilities for different periods
        foreach ($this->config['volatility']['rolling_periods'] as $period) {
            if ($count >= $period) {
                $result["rolling_volatility_{$period}"] = $this->calculateRollingVolatility($prices, $period);
            }
        }

        return $result;
    }

    /**
     * Perform statistical tests
     */
    private function performStatisticalTests(array $rawData, array $cleanData): array
    {
        $rawPrices = array_column($rawData, 'price');
        $cleanPrices = array_column($cleanData, 'price');

        return [
            'normality_test' => $this->testNormality($cleanPrices),
            'outlier_impact' => $this->calculateOutlierImpact($rawPrices, $cleanPrices),
            'data_consistency' => $this->testDataConsistency($cleanPrices),
        ];
    }

    /**
     * Calculate data quality metrics
     */
    private function calculateDataQualityMetrics(array $rawData, array $cleanData, array $outliers): array
    {
        $rawCount = count($rawData);
        $cleanCount = count($cleanData);
        $outlierCount = count($outliers);

        return [
            'total_data_points' => $rawCount,
            'clean_data_points' => $cleanCount,
            'outliers_removed' => $outlierCount,
            'data_retention_rate' => $rawCount > 0 ? round(($cleanCount / $rawCount) * 100, 2) : 0,
            'outlier_rate' => $rawCount > 0 ? round(($outlierCount / $rawCount) * 100, 2) : 0,
            'quality_score' => $this->calculateQualityScore($rawCount, $cleanCount, $outlierCount),
            'data_completeness' => $this->calculateDataCompleteness($rawData),
        ];
    }

    // Helper methods

    private function calculateMedian(array $values): float
    {
        sort($values);
        $count = count($values);

        if ($count == 0) {
            return 0;
        }

        $middle = $count / 2;

        if ($count % 2 == 0) {
            return ($values[$middle - 1] + $values[$middle]) / 2;
        } else {
            return $values[floor($middle)];
        }
    }

    private function calculateMode(array $values): ?float
    {
        // Filter to only numeric values and convert to strings for array_count_values
        $numericValues = array_filter($values, 'is_numeric');
        if (empty($numericValues)) {
            return null;
        }

        // Convert to strings for counting, then back to numbers
        $stringValues = array_map('strval', $numericValues);
        $frequencies = array_count_values($stringValues);
        $maxFreq = max($frequencies);

        if ($maxFreq == 1) {
            return null; // No mode if all values appear once
        }

        $modes = array_keys($frequencies, $maxFreq);

        return (float) $modes[0]; // Return first mode if multiple
    }

    private function calculateSkewness(array $values, float $mean, float $stdDev): float
    {
        if ($stdDev == 0) {
            return 0;
        }

        $n = count($values);
        $sum = array_sum(array_map(fn ($x) => pow(($x - $mean) / $stdDev, 3), $values));

        return ($n / (($n - 1) * ($n - 2))) * $sum;
    }

    private function calculateKurtosis(array $values, float $mean, float $stdDev): float
    {
        if ($stdDev == 0) {
            return 0;
        }

        $n = count($values);
        $sum = array_sum(array_map(fn ($x) => pow(($x - $mean) / $stdDev, 4), $values));

        return (($n * ($n + 1)) / (($n - 1) * ($n - 2) * ($n - 3))) * $sum -
               (3 * pow($n - 1, 2)) / (($n - 2) * ($n - 3));
    }

    private function calculatePercentile(array $sortedValues, int $percentile): float
    {
        $index = ($percentile / 100) * (count($sortedValues) - 1);
        $lower = floor($index);
        $upper = ceil($index);

        if ($lower == $upper) {
            return $sortedValues[$lower];
        }

        $weight = $index - $lower;

        return $sortedValues[$lower] * (1 - $weight) + $sortedValues[$upper] * $weight;
    }

    private function getZScoreForConfidenceLevel(float $confidenceLevel): float
    {
        $zScores = [
            0.90 => 1.645,
            0.95 => 1.960,
            0.99 => 2.576,
        ];

        return $zScores[$confidenceLevel] ?? 1.960; // Default to 95%
    }

    private function getTrendStrength(float $rSquared): string
    {
        if ($rSquared > 0.7) {
            return 'strong';
        }
        if ($rSquared > 0.4) {
            return 'moderate';
        }
        if ($rSquared > 0.1) {
            return 'weak';
        }

        return 'very_weak';
    }

    private function classifyVolatility(float $stdDev, float $mean): string
    {
        $cv = $mean != 0 ? ($stdDev / $mean) * 100 : 0;

        if ($cv < 5) {
            return 'very_low';
        }
        if ($cv < 15) {
            return 'low';
        }
        if ($cv < 30) {
            return 'moderate';
        }
        if ($cv < 50) {
            return 'high';
        }

        return 'very_high';
    }

    private function calculateRollingVolatility(array $prices, int $period): array
    {
        $rollingVols = [];
        $count = count($prices);

        for ($i = $period - 1; $i < $count; $i++) {
            $window = array_slice($prices, $i - $period + 1, $period);
            $mean = array_sum($window) / $period;
            $variance = array_sum(array_map(fn ($x) => pow($x - $mean, 2), $window)) / ($period - 1);
            $rollingVols[] = sqrt($variance);
        }

        return [
            'values' => array_map(fn ($v) => round($v, 8), $rollingVols),
            'average' => count($rollingVols) > 0 ? round(array_sum($rollingVols) / count($rollingVols), 8) : 0,
            'min' => count($rollingVols) > 0 ? round(min($rollingVols), 8) : 0,
            'max' => count($rollingVols) > 0 ? round(max($rollingVols), 8) : 0,
        ];
    }

    private function testNormality(array $prices): array
    {
        if (count($prices) < 3) {
            return ['test' => 'insufficient_data'];
        }

        $mean = array_sum($prices) / count($prices);
        $stdDev = sqrt(array_sum(array_map(fn ($x) => pow($x - $mean, 2), $prices)) / (count($prices) - 1));

        $skewness = $this->calculateSkewness($prices, $mean, $stdDev);
        $kurtosis = $this->calculateKurtosis($prices, $mean, $stdDev);

        // Simple normality assessment based on skewness and kurtosis
        $normalityScore = 1 - (abs($skewness) / 3 + abs($kurtosis - 3) / 3) / 2;
        $normalityScore = max(0, min(1, $normalityScore));

        return [
            'skewness' => round($skewness, 4),
            'kurtosis' => round($kurtosis, 4),
            'normality_score' => round($normalityScore, 4),
            'assessment' => $normalityScore > 0.7 ? 'likely_normal' : ($normalityScore > 0.3 ? 'moderately_normal' : 'likely_non_normal'),
        ];
    }

    private function calculateOutlierImpact(array $rawPrices, array $cleanPrices): array
    {
        if (empty($rawPrices) || empty($cleanPrices)) {
            return ['impact' => 'no_data'];
        }

        $rawMean = array_sum($rawPrices) / count($rawPrices);
        $cleanMean = array_sum($cleanPrices) / count($cleanPrices);

        $meanDifference = abs($rawMean - $cleanMean);
        $percentageImpact = $rawMean != 0 ? ($meanDifference / $rawMean) * 100 : 0;

        return [
            'raw_mean' => round($rawMean, 8),
            'clean_mean' => round($cleanMean, 8),
            'absolute_difference' => round($meanDifference, 8),
            'percentage_impact' => round($percentageImpact, 4),
            'impact_level' => $percentageImpact < 1 ? 'minimal' : ($percentageImpact < 5 ? 'moderate' : 'significant'),
        ];
    }

    private function testDataConsistency(array $prices): array
    {
        if (count($prices) < 2) {
            return ['consistency' => 'insufficient_data'];
        }

        $mean = array_sum($prices) / count($prices);
        $stdDev = sqrt(array_sum(array_map(fn ($x) => pow($x - $mean, 2), $prices)) / (count($prices) - 1));
        $cv = $mean != 0 ? ($stdDev / $mean) * 100 : 0;

        return [
            'coefficient_of_variation' => round($cv, 4),
            'consistency_level' => $cv < 10 ? 'high' : ($cv < 25 ? 'moderate' : 'low'),
            'data_spread' => $this->classifyVolatility($stdDev, $mean),
        ];
    }

    private function calculateQualityScore(int $rawCount, int $cleanCount, int $outlierCount): float
    {
        if ($rawCount == 0) {
            return 0;
        }

        $retentionRate = $cleanCount / $rawCount;
        $outlierRate = $outlierCount / $rawCount;

        // Quality score based on data retention and outlier rate
        $score = $retentionRate * (1 - min($outlierRate * 2, 0.5)); // Penalize high outlier rates

        return round(max(0, min(1, $score)), 4);
    }

    private function calculateDataCompleteness(array $rawData): array
    {
        $totalFields = count($rawData) * 7; // 7 fields per record
        $completeFields = 0;

        foreach ($rawData as $record) {
            $completeFields += count(array_filter([
                $record['price'] ?? null,
                $record['volume'] ?? null,
                $record['min_amount'] ?? null,
                $record['max_amount'] ?? null,
                $record['timestamp'] ?? null,
                $record['merchant_trades'] ?? null,
                $record['completion_rate'] ?? null,
            ], fn ($value) => $value !== null && $value !== ''));
        }

        $completeness = $totalFields > 0 ? ($completeFields / $totalFields) * 100 : 0;

        return [
            'percentage' => round($completeness, 2),
            'level' => $completeness > 90 ? 'excellent' : ($completeness > 75 ? 'good' : ($completeness > 50 ? 'fair' : 'poor')),
        ];
    }

    private function getEmptyAnalysis(): array
    {
        return [
            'raw_statistics' => $this->getEmptyStatistics(),
            'cleaned_statistics' => $this->getEmptyStatistics(),
            'outlier_analysis' => ['method_used' => null, 'outliers_detected' => 0, 'outlier_percentage' => 0],
            'weighted_averages' => [],
            'time_weighted_averages' => [],
            'confidence_intervals' => [],
            'percentile_analysis' => [],
            'trend_analysis' => ['trend' => 'no_data'],
            'volatility_analysis' => ['volatility' => 0],
            'statistical_tests' => ['test' => 'no_data'],
            'quality_metrics' => ['quality_score' => 0],
        ];
    }

    private function getEmptyStatistics(): array
    {
        return [
            'count' => 0,
            'mean' => 0,
            'median' => 0,
            'mode' => null,
            'standard_deviation' => 0,
            'variance' => 0,
            'min' => 0,
            'max' => 0,
            'range' => 0,
            'coefficient_of_variation' => 0,
            'skewness' => 0,
            'kurtosis' => 0,
        ];
    }
}
