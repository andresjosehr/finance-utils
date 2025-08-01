<?php

namespace App\Services;

class P2PDataValidationService
{
    private const MIN_ADS_COUNT = 1;

    private const MAX_PRICE_VARIATION = 0.5; // 50% variation allowed

    private const MIN_PRICE = 0.01;

    private const MAX_PRICE = 1000000;

    /**
     * Validate API response data
     */
    public function validateApiData(array $data, string $tradeType): array
    {
        $result = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => [],
            'quality_score' => 1.0,
            'metrics' => [],
        ];

        // Check basic structure
        $structureValidation = $this->validateStructure($data);
        if (! $structureValidation['is_valid']) {
            $result['is_valid'] = false;
            $result['errors'] = array_merge($result['errors'], $structureValidation['errors']);
            $result['quality_score'] *= 0.5;
        }

        // If structure is invalid, return early
        if (! $result['is_valid']) {
            return $result;
        }

        // Validate ads data
        $adsValidation = $this->validateAdsData($data['data'] ?? []);
        $result['errors'] = array_merge($result['errors'], $adsValidation['errors']);
        $result['warnings'] = array_merge($result['warnings'], $adsValidation['warnings']);
        $result['quality_score'] *= $adsValidation['quality_multiplier'];
        $result['metrics'] = $adsValidation['metrics'];

        // Price consistency validation
        $priceValidation = $this->validatePriceConsistency($data['data'] ?? []);
        $result['warnings'] = array_merge($result['warnings'], $priceValidation['warnings']);
        $result['quality_score'] *= $priceValidation['quality_multiplier'];

        // Trade type specific validation
        $tradeTypeValidation = $this->validateTradeTypeSpecific($data['data'] ?? [], $tradeType);
        $result['warnings'] = array_merge($result['warnings'], $tradeTypeValidation['warnings']);

        // Final quality assessment
        if ($result['quality_score'] < 0.3) {
            $result['is_valid'] = false;
            $result['errors'][] = 'Data quality score too low: '.round($result['quality_score'], 3);
        }

        return $result;
    }

    /**
     * Validate basic API response structure
     */
    private function validateStructure(array $data): array
    {
        $result = ['is_valid' => true, 'errors' => []];

        // Check required fields
        if (! isset($data['code'])) {
            $result['is_valid'] = false;
            $result['errors'][] = 'Missing response code';
        }

        if (! isset($data['data']) || ! is_array($data['data'])) {
            $result['is_valid'] = false;
            $result['errors'][] = 'Missing or invalid data array';
        }

        // Check response code
        if (isset($data['code']) && $data['code'] !== '000000') {
            $result['is_valid'] = false;
            $result['errors'][] = 'API returned error code: '.$data['code'];

            if (isset($data['message'])) {
                $result['errors'][] = 'API error message: '.$data['message'];
            }
        }

        return $result;
    }

    /**
     * Validate individual ads data
     */
    private function validateAdsData(array $ads): array
    {
        $result = [
            'errors' => [],
            'warnings' => [],
            'quality_multiplier' => 1.0,
            'metrics' => [
                'total_ads' => count($ads),
                'valid_ads' => 0,
                'invalid_ads' => 0,
                'incomplete_ads' => 0,
            ],
        ];

        if (empty($ads)) {
            $result['errors'][] = 'No ads data available';
            $result['quality_multiplier'] = 0.0;

            return $result;
        }

        if (count($ads) < self::MIN_ADS_COUNT) {
            $result['warnings'][] = 'Very few ads available: '.count($ads);
            $result['quality_multiplier'] *= 0.8;
        }

        foreach ($ads as $index => $ad) {
            $adValidation = $this->validateSingleAd($ad, $index);

            if (! empty($adValidation['errors'])) {
                $result['metrics']['invalid_ads']++;
                $result['errors'] = array_merge($result['errors'], $adValidation['errors']);
            } elseif (! empty($adValidation['warnings'])) {
                $result['metrics']['incomplete_ads']++;
                $result['warnings'] = array_merge($result['warnings'], $adValidation['warnings']);
            } else {
                $result['metrics']['valid_ads']++;
            }
        }

        // Calculate quality based on valid ads ratio
        $validRatio = $result['metrics']['valid_ads'] / $result['metrics']['total_ads'];
        if ($validRatio < 0.5) {
            $result['quality_multiplier'] *= 0.5;
            $result['warnings'][] = 'Low valid ads ratio: '.round($validRatio * 100, 1).'%';
        } elseif ($validRatio < 0.8) {
            $result['quality_multiplier'] *= 0.8;
        }

        return $result;
    }

    /**
     * Validate a single ad
     */
    private function validateSingleAd(array $ad, int $index): array
    {
        $result = ['errors' => [], 'warnings' => []];

        // Check required ad structure
        if (! isset($ad['adv']) || ! is_array($ad['adv'])) {
            $result['errors'][] = "Ad {$index}: Missing 'adv' data";

            return $result;
        }

        if (! isset($ad['advertiser']) || ! is_array($ad['advertiser'])) {
            $result['errors'][] = "Ad {$index}: Missing 'advertiser' data";

            return $result;
        }

        $adv = $ad['adv'];
        $advertiser = $ad['advertiser'];

        // Validate price
        if (! isset($adv['price']) || ! is_numeric($adv['price'])) {
            $result['errors'][] = "Ad {$index}: Invalid or missing price";
        } else {
            $price = (float) $adv['price'];
            if ($price < self::MIN_PRICE || $price > self::MAX_PRICE) {
                $result['errors'][] = "Ad {$index}: Price out of valid range: {$price}";
            }
        }

        // Validate surplus amount (available volume)
        if (! isset($adv['surplusAmount']) || ! is_numeric($adv['surplusAmount'])) {
            $result['warnings'][] = "Ad {$index}: Invalid or missing surplus amount";
        } else {
            $amount = (float) $adv['surplusAmount'];
            if ($amount <= 0) {
                $result['warnings'][] = "Ad {$index}: Zero or negative surplus amount";
            }
        }

        // Validate advertiser data
        if (empty($advertiser['nickName'])) {
            $result['warnings'][] = "Ad {$index}: Missing advertiser nickname";
        }

        // Validate trade methods
        if (! isset($adv['tradeMethods']) || ! is_array($adv['tradeMethods'])) {
            $result['warnings'][] = "Ad {$index}: Missing trade methods";
        } elseif (empty($adv['tradeMethods'])) {
            $result['warnings'][] = "Ad {$index}: No trade methods available";
        }

        // Validate min/max trade amounts
        if (isset($adv['minSingleTransAmount'], $adv['maxSingleTransAmount'])) {
            $minAmount = (float) $adv['minSingleTransAmount'];
            $maxAmount = (float) $adv['maxSingleTransAmount'];

            if ($minAmount > $maxAmount) {
                $result['warnings'][] = "Ad {$index}: Min amount greater than max amount";
            }
        }

        return $result;
    }

    /**
     * Validate price consistency across ads
     */
    private function validatePriceConsistency(array $ads): array
    {
        $result = [
            'warnings' => [],
            'quality_multiplier' => 1.0,
        ];

        if (count($ads) < 2) {
            return $result;
        }

        $prices = [];
        foreach ($ads as $ad) {
            if (isset($ad['adv']['price']) && is_numeric($ad['adv']['price'])) {
                $prices[] = (float) $ad['adv']['price'];
            }
        }

        if (count($prices) < 2) {
            return $result;
        }

        // Calculate price statistics
        $minPrice = min($prices);
        $maxPrice = max($prices);
        $avgPrice = array_sum($prices) / count($prices);

        // Check for extreme price variations
        if ($minPrice > 0) {
            $variation = ($maxPrice - $minPrice) / $minPrice;

            if ($variation > self::MAX_PRICE_VARIATION) {
                $result['warnings'][] = 'High price variation detected: '.round($variation * 100, 1).'%';
                $result['quality_multiplier'] *= 0.8;
            }
        }

        // Check for price outliers (prices that are way off from the average)
        $outliers = 0;
        foreach ($prices as $price) {
            $deviation = abs($price - $avgPrice) / $avgPrice;
            if ($deviation > 0.2) { // 20% deviation from average
                $outliers++;
            }
        }

        if ($outliers > count($prices) * 0.1) { // More than 10% outliers
            $result['warnings'][] = "Multiple price outliers detected: {$outliers} out of ".count($prices);
            $result['quality_multiplier'] *= 0.9;
        }

        return $result;
    }

    /**
     * Trade type specific validation
     */
    private function validateTradeTypeSpecific(array $ads, string $tradeType): array
    {
        $result = ['warnings' => []];

        if (empty($ads)) {
            return $result;
        }

        // For BUY orders, prices should generally be sorted ascending (best buy price first)
        // For SELL orders, prices should generally be sorted ascending (best sell price first)
        $prices = array_filter(array_map(function ($ad) {
            return isset($ad['adv']['price']) ? (float) $ad['adv']['price'] : null;
        }, $ads));

        if (count($prices) >= 3) {
            $isAscending = true;
            $isDescending = true;

            for ($i = 1; $i < count($prices); $i++) {
                if ($prices[$i - 1] > $prices[$i]) {
                    $isAscending = false;
                }
                if ($prices[$i - 1] < $prices[$i]) {
                    $isDescending = false;
                }
            }

            // Expect ascending order for both BUY and SELL (Binance sorts by best price first)
            if (! $isAscending && ! $isDescending) {
                $result['warnings'][] = "Prices not properly sorted for {$tradeType} orders";
            }
        }

        return $result;
    }

    /**
     * Detect anomalies in collected data
     */
    public function detectAnomalies(array $currentData, array $historicalData = []): array
    {
        $anomalies = [];

        if (empty($currentData['data'])) {
            return ['anomalies' => ['No current data available']];
        }

        // Extract current metrics
        $currentPrices = array_map(function ($ad) {
            return (float) $ad['adv']['price'];
        }, $currentData['data']);

        $currentAvgPrice = array_sum($currentPrices) / count($currentPrices);
        $currentAdsCount = count($currentData['data']);

        // Compare with historical data if available
        if (! empty($historicalData)) {
            foreach ($historicalData as $historical) {
                if (empty($historical['data'])) {
                    continue;
                }

                $historicalPrices = array_map(function ($ad) {
                    return (float) $ad['adv']['price'];
                }, $historical['data']);

                $historicalAvgPrice = array_sum($historicalPrices) / count($historicalPrices);
                $historicalAdsCount = count($historical['data']);

                // Check for significant price changes
                if ($historicalAvgPrice > 0) {
                    $priceChange = abs($currentAvgPrice - $historicalAvgPrice) / $historicalAvgPrice;
                    if ($priceChange > 0.15) { // 15% change
                        $anomalies[] = sprintf(
                            'Significant price change: %.2f%% (from %.2f to %.2f)',
                            $priceChange * 100,
                            $historicalAvgPrice,
                            $currentAvgPrice
                        );
                    }
                }

                // Check for significant changes in ads count
                $adsChangeRatio = abs($currentAdsCount - $historicalAdsCount) / max($historicalAdsCount, 1);
                if ($adsChangeRatio > 0.5) { // 50% change in ads count
                    $anomalies[] = sprintf(
                        'Significant change in ads count: from %d to %d (%.1f%% change)',
                        $historicalAdsCount,
                        $currentAdsCount,
                        $adsChangeRatio * 100
                    );
                }
            }
        }

        // Check for internal anomalies in current data
        if (count($currentPrices) > 2) {
            $median = $this->calculateMedian($currentPrices);
            $q1 = $this->calculateQuantile($currentPrices, 0.25);
            $q3 = $this->calculateQuantile($currentPrices, 0.75);
            $iqr = $q3 - $q1;

            // Check for outliers using IQR method
            $outliers = array_filter($currentPrices, function ($price) use ($q1, $q3, $iqr) {
                return $price < ($q1 - 1.5 * $iqr) || $price > ($q3 + 1.5 * $iqr);
            });

            if (count($outliers) > count($currentPrices) * 0.1) {
                $anomalies[] = sprintf(
                    'Multiple price outliers detected: %d out of %d prices',
                    count($outliers),
                    count($currentPrices)
                );
            }
        }

        return ['anomalies' => $anomalies];
    }

    /**
     * Calculate median of array
     */
    private function calculateMedian(array $numbers): float
    {
        sort($numbers);
        $count = count($numbers);

        if ($count % 2 === 0) {
            return ($numbers[$count / 2 - 1] + $numbers[$count / 2]) / 2;
        } else {
            return $numbers[intval($count / 2)];
        }
    }

    /**
     * Calculate quantile of array
     */
    private function calculateQuantile(array $numbers, float $quantile): float
    {
        sort($numbers);
        $count = count($numbers);
        $index = $quantile * ($count - 1);

        if (floor($index) === $index) {
            return $numbers[intval($index)];
        } else {
            $lower = $numbers[intval(floor($index))];
            $upper = $numbers[intval(ceil($index))];

            return $lower + ($upper - $lower) * ($index - floor($index));
        }
    }

    /**
     * Generate validation report
     */
    public function generateValidationReport(array $snapshots): array
    {
        $report = [
            'total_snapshots' => count($snapshots),
            'validation_summary' => [
                'valid' => 0,
                'invalid' => 0,
                'quality_scores' => [],
            ],
            'common_issues' => [],
            'recommendations' => [],
        ];

        $allIssues = [];

        foreach ($snapshots as $snapshot) {
            $validation = $this->validateApiData(
                $snapshot['raw_data'] ?? [],
                $snapshot['trade_type'] ?? 'BUY'
            );

            $report['validation_summary']['quality_scores'][] = $validation['quality_score'];

            if ($validation['is_valid']) {
                $report['validation_summary']['valid']++;
            } else {
                $report['validation_summary']['invalid']++;
            }

            // Collect all issues
            $allIssues = array_merge($allIssues, $validation['errors'], $validation['warnings']);
        }

        // Find common issues
        $issueCounts = array_count_values($allIssues);
        arsort($issueCounts);
        $report['common_issues'] = array_slice($issueCounts, 0, 10, true);

        // Calculate statistics
        if (! empty($report['validation_summary']['quality_scores'])) {
            $scores = $report['validation_summary']['quality_scores'];
            $report['validation_summary']['average_quality'] = array_sum($scores) / count($scores);
            $report['validation_summary']['min_quality'] = min($scores);
            $report['validation_summary']['max_quality'] = max($scores);
        }

        // Generate recommendations
        if ($report['validation_summary']['average_quality'] < 0.7) {
            $report['recommendations'][] = 'Consider increasing API timeout or retry attempts';
        }

        if ($report['validation_summary']['invalid'] > $report['validation_summary']['valid'] * 0.1) {
            $report['recommendations'][] = 'High number of invalid responses - check API parameters';
        }

        return $report;
    }
}
