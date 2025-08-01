<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class BinanceP2PService
{
    private const API_URL = 'https://p2p.binance.com/bapi/c2c/v2/friendly/c2c/adv/search';

    private const RATE_LIMIT_KEY = 'binance-p2p-api';

    private const RATE_LIMIT_MAX_ATTEMPTS = 60; // 60 requests per minute

    private const RETRY_ATTEMPTS = 3;

    private const RETRY_DELAY_MS = 1000; // 1 second between retries

    private array $defaultHeaders = [
        'accept' => '*/*',
        'accept-language' => 'es-ES,es;q=0.9,en;q=0.8',
        'bnc-level' => '0',
        'bnc-location' => 'VE',
        'bnc-time-zone' => 'America/Caracas',
        'c2ctype' => 'c2c_web',
        'cache-control' => 'no-cache',
        'clienttype' => 'web',
        'content-type' => 'application/json',
        'lang' => 'es',
        'origin' => 'https://p2p.binance.com',
        'pragma' => 'no-cache',
        'priority' => 'u=1, i',
        'referer' => 'https://p2p.binance.com/es',
        'sec-ch-ua' => '"Not)A;Brand";v="8", "Chromium";v="138", "Google Chrome";v="138"',
        'sec-ch-ua-mobile' => '?0',
        'sec-ch-ua-platform' => '"Windows"',
        'sec-fetch-dest' => 'empty',
        'sec-fetch-mode' => 'cors',
        'sec-fetch-site' => 'same-origin',
        'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',
    ];

    /**
     * Get P2P data with retry logic and rate limiting
     */
    public function getP2PData(
        string $asset = 'USDT',
        string $fiat = 'VES',
        string $tradeType = 'BUY',
        int $page = 1,
        int $rows = 50,
        ?float $transAmount = null,
        array $volumeRanges = []
    ): ?array {
        // Check rate limit
        if (RateLimiter::tooManyAttempts(self::RATE_LIMIT_KEY, self::RATE_LIMIT_MAX_ATTEMPTS)) {
            Log::warning('Binance P2P API rate limit exceeded', [
                'asset' => $asset,
                'fiat' => $fiat,
                'trade_type' => $tradeType,
            ]);

            return null;
        }

        // Use configurable volume ranges or default sampling strategy
        if (! empty($volumeRanges)) {
            return $this->collectMultiVolumeData($asset, $fiat, $tradeType, $page, $rows, $volumeRanges);
        }

        $payload = [
            'fiat' => strtoupper($fiat),
            'page' => $page,
            'rows' => min($rows, 20), // Increased from 10 to get more diverse pricing
            'transAmount' => $transAmount ?? 500, // Reduced from 1000 to get more realistic mid-tier pricing
            'tradeType' => strtoupper($tradeType),
            'asset' => strtoupper($asset),
            'countries' => [],
            'proMerchantAds' => false,
            'shieldMerchantAds' => false,
            'filterType' => 'all',
            'periods' => [],
            'additionalKycVerifyFilter' => 0,
            'publisherType' => 'merchant',
            'payTypes' => [],
            'classifies' => ['mass', 'profession', 'fiat_trade'],
            'tradedWith' => false,
            'followed' => false,
        ];

        // Execute with retry logic
        return $this->executeWithRetry(function () use ($payload) {
            RateLimiter::hit(self::RATE_LIMIT_KEY);

            $startTime = microtime(true);

            $response = Http::withHeaders($this->defaultHeaders)
                ->timeout(30)
                ->connectTimeout(10)
                ->post(self::API_URL, $payload);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            if ($response->successful()) {
                $data = $response->json();

                // Validate response structure
                if (! $this->validateApiResponse($data)) {
                    throw new Exception('Invalid API response structure');
                }

                // Log successful request
                Log::debug('Binance P2P API request successful', [
                    'asset' => $payload['asset'],
                    'fiat' => $payload['fiat'],
                    'trade_type' => $payload['tradeType'],
                    'response_time_ms' => $responseTime,
                    'ads_count' => count($data['data'] ?? []),
                ]);

                return $data;
            }

            // Handle specific HTTP errors
            $this->handleHttpError($response, $payload);

            return null;
        });
    }

    public function getBuyPrices(string $asset = 'USDT', string $fiat = 'VES', int $rows = 50): ?array
    {
        return $this->getP2PData($asset, $fiat, 'BUY', 1, $rows);
    }

    public function getSellPrices(string $asset = 'USDT', string $fiat = 'VES', int $rows = 50): ?array
    {
        return $this->getP2PData($asset, $fiat, 'SELL', 1, $rows);
    }

    public function getBothPrices(string $asset = 'USDT', string $fiat = 'VES', int $rows = 50): array
    {
        $buyData = $this->getBuyPrices($asset, $fiat, $rows);
        $sellData = $this->getSellPrices($asset, $fiat, $rows);

        return [
            'buy' => $buyData,
            'sell' => $sellData,
        ];
    }

    public function calculatePriceMetrics(array $priceData): array
    {
        if (empty($priceData['data'])) {
            return [
                'average' => 0,
                'best' => 0,
                'worst' => 0,
                'count' => 0,
                'spread' => 0,
            ];
        }

        $prices = collect($priceData['data'])->map(function ($item) {
            return (float) $item['adv']['price'];
        })->values();

        $average = $prices->avg();
        $best = $prices->first(); // Primera oferta (mejor precio)
        $worst = $prices->last(); // Última oferta (peor precio)
        $count = $prices->count();
        $spread = $count > 1 ? $worst - $best : 0;

        return [
            'average' => round($average, 2),
            'best' => round($best, 2),
            'worst' => round($worst, 2),
            'count' => $count,
            'spread' => round($spread, 2),
        ];
    }

    public function getMarketSummary(string $asset = 'USDT', string $fiat = 'VES', int $rows = 20): array
    {
        $data = $this->getBothPrices($asset, $fiat, $rows);

        $buyMetrics = $data['buy'] ? $this->calculatePriceMetrics($data['buy']) : null;
        $sellMetrics = $data['sell'] ? $this->calculatePriceMetrics($data['sell']) : null;

        $summary = [
            'asset' => $asset,
            'fiat' => $fiat,
            'timestamp' => now()->toISOString(),
            'buy' => $buyMetrics,
            'sell' => $sellMetrics,
            'market_spread' => null,
            'arbitrage_opportunity' => null,
        ];

        // Calcular spread del mercado y oportunidad de arbitraje
        if ($buyMetrics && $sellMetrics && $buyMetrics['best'] > 0 && $sellMetrics['best'] > 0) {
            $summary['market_spread'] = round($sellMetrics['best'] - $buyMetrics['best'], 2);
            $summary['arbitrage_opportunity'] = $summary['market_spread'] > 0;
        }

        return $summary;
    }

    /**
     * Execute API call with retry logic
     */
    private function executeWithRetry(callable $callback): mixed
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= self::RETRY_ATTEMPTS; $attempt++) {
            try {
                return $callback();
            } catch (Exception $e) {
                $lastException = $e;

                Log::warning('Binance P2P API attempt failed', [
                    'attempt' => $attempt,
                    'max_attempts' => self::RETRY_ATTEMPTS,
                    'error' => $e->getMessage(),
                ]);

                // Don't retry on last attempt
                if ($attempt < self::RETRY_ATTEMPTS) {
                    // Exponential backoff: delay increases with each attempt
                    $delay = self::RETRY_DELAY_MS * pow(2, $attempt - 1);
                    usleep($delay * 1000); // Convert to microseconds
                }
            }
        }

        // Log final failure
        Log::error('Binance P2P API request failed after all retries', [
            'attempts' => self::RETRY_ATTEMPTS,
            'final_error' => $lastException?->getMessage(),
        ]);

        return null;
    }

    /**
     * Validate API response structure
     */
    private function validateApiResponse(array $data): bool
    {
        // Check required fields
        if (! isset($data['code']) || ! isset($data['data'])) {
            return false;
        }

        // Check success code
        if ($data['code'] !== '000000') {
            Log::warning('Binance P2P API returned error code', [
                'code' => $data['code'],
                'message' => $data['message'] ?? 'Unknown error',
            ]);

            return false;
        }

        // Validate data array
        if (! is_array($data['data'])) {
            return false;
        }

        return true;
    }

    /**
     * Handle HTTP errors with specific logic
     */
    private function handleHttpError($response, array $payload): void
    {
        $status = $response->status();
        $body = $response->body();

        $logData = [
            'status' => $status,
            'payload' => $payload,
            'response_body' => $body,
        ];

        switch ($status) {
            case 429:
                Log::warning('Binance P2P API rate limit hit', $logData);
                // Clear our rate limiter to sync with server
                RateLimiter::clear(self::RATE_LIMIT_KEY);
                break;

            case 403:
                Log::error('Binance P2P API access forbidden', $logData);
                break;

            case 400:
                Log::error('Binance P2P API bad request', $logData);
                break;

            case 500:
            case 502:
            case 503:
                Log::warning('Binance P2P API server error', $logData);
                break;

            default:
                Log::warning('Binance P2P API unexpected error', $logData);
        }
    }

    /**
     * Get enhanced price metrics with additional statistics
     */
    public function getEnhancedPriceMetrics(array $priceData): array
    {
        $basicMetrics = $this->calculatePriceMetrics($priceData);

        if (empty($priceData['data'])) {
            return array_merge($basicMetrics, [
                'volume_weighted_average' => 0,
                'median' => 0,
                'standard_deviation' => 0,
                'total_volume' => 0,
                'price_distribution' => [],
            ]);
        }

        $ads = collect($priceData['data']);
        $prices = $ads->pluck('adv.price')->map(fn ($p) => (float) $p);
        $volumes = $ads->pluck('adv.surplusAmount')->map(fn ($v) => (float) $v);

        // Volume weighted average
        $totalVolume = $volumes->sum();
        $weightedSum = $ads->sum(function ($ad) {
            return (float) $ad['adv']['price'] * (float) $ad['adv']['surplusAmount'];
        });

        $volumeWeightedAvg = $totalVolume > 0 ? $weightedSum / $totalVolume : 0;

        // Standard deviation
        $mean = $prices->avg();
        $variance = $prices->map(fn ($price) => pow($price - $mean, 2))->avg();
        $standardDeviation = sqrt($variance);

        // Price distribution (quartiles)
        $sortedPrices = $prices->sort()->values();
        $count = $sortedPrices->count();

        $distribution = [];
        if ($count > 0) {
            $distribution = [
                'q1' => $sortedPrices[intval($count * 0.25)] ?? 0,
                'q2' => $sortedPrices[intval($count * 0.5)] ?? 0,
                'q3' => $sortedPrices[intval($count * 0.75)] ?? 0,
                'p90' => $sortedPrices[intval($count * 0.9)] ?? 0,
                'p95' => $sortedPrices[intval($count * 0.95)] ?? 0,
            ];
        }

        return array_merge($basicMetrics, [
            'volume_weighted_average' => round($volumeWeightedAvg, 2),
            'median' => round($prices->median(), 2),
            'standard_deviation' => round($standardDeviation, 2),
            'total_volume' => round($totalVolume, 2),
            'price_distribution' => $distribution,
        ]);
    }

    /**
     * Collect data across multiple volume ranges for better price distribution
     */
    private function collectMultiVolumeData(
        string $asset,
        string $fiat,
        string $tradeType,
        int $page,
        int $rows,
        array $volumeRanges
    ): ?array {
        $combinedData = [
            'code' => '000000',
            'message' => null,
            'data' => [],
            'total' => 0,
            'success' => true,
            'volume_sampling_metadata' => [
                'ranges_used' => $volumeRanges,
                'collection_strategy' => 'multi_volume_sampling',
            ],
        ];

        $totalAdsCollected = 0;
        $rowsPerRange = max(1, intval($rows / count($volumeRanges)));

        foreach ($volumeRanges as $volumeRange) {
            $rangeData = $this->getSingleVolumeRangeData(
                $asset,
                $fiat,
                $tradeType,
                $page,
                $rowsPerRange,
                $volumeRange
            );

            if ($rangeData && isset($rangeData['data']) && is_array($rangeData['data'])) {
                // Add volume range metadata to each ad
                foreach ($rangeData['data'] as &$ad) {
                    $ad['volume_range_metadata'] = [
                        'target_volume' => $volumeRange,
                        'collection_method' => 'volume_stratified',
                    ];
                }

                $combinedData['data'] = array_merge($combinedData['data'], $rangeData['data']);
                $totalAdsCollected += count($rangeData['data']);
            }
        }

        $combinedData['total'] = $totalAdsCollected;

        // Sort by price to maintain consistent ordering
        if (! empty($combinedData['data'])) {
            usort($combinedData['data'], function ($a, $b) use ($tradeType) {
                $priceA = (float) $a['adv']['price'];
                $priceB = (float) $b['adv']['price'];

                // For BUY orders: lower prices are better (ascending)
                // For SELL orders: higher prices are better (descending)
                return $tradeType === 'BUY' ? $priceA <=> $priceB : $priceB <=> $priceA;
            });
        }

        Log::debug('Multi-volume data collection completed', [
            'asset' => $asset,
            'fiat' => $fiat,
            'trade_type' => $tradeType,
            'volume_ranges' => $volumeRanges,
            'total_ads' => $totalAdsCollected,
        ]);

        return $combinedData;
    }

    /**
     * Get data for a specific volume range
     */
    private function getSingleVolumeRangeData(
        string $asset,
        string $fiat,
        string $tradeType,
        int $page,
        int $rows,
        float $targetVolume
    ): ?array {
        $payload = [
            'fiat' => strtoupper($fiat),
            'page' => $page,
            'rows' => min($rows, 20),
            'transAmount' => $targetVolume,
            'tradeType' => strtoupper($tradeType),
            'asset' => strtoupper($asset),
            'countries' => [],
            'proMerchantAds' => false,
            'shieldMerchantAds' => false,
            'filterType' => 'all',
            'periods' => [],
            'additionalKycVerifyFilter' => 0,
            'publisherType' => 'merchant',
            'payTypes' => [],
            'classifies' => ['mass', 'profession', 'fiat_trade'],
            'tradedWith' => false,
            'followed' => false,
        ];

        return $this->executeWithRetry(function () use ($payload) {
            RateLimiter::hit(self::RATE_LIMIT_KEY);

            $startTime = microtime(true);

            $response = Http::withHeaders($this->defaultHeaders)
                ->timeout(30)
                ->connectTimeout(10)
                ->post(self::API_URL, $payload);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            if ($response->successful()) {
                $data = $response->json();

                if (! $this->validateApiResponse($data)) {
                    throw new Exception('Invalid API response structure');
                }

                Log::debug('Volume range data collected', [
                    'volume' => $payload['transAmount'],
                    'trade_type' => $payload['tradeType'],
                    'response_time_ms' => $responseTime,
                    'ads_count' => count($data['data'] ?? []),
                ]);

                return $data;
            }

            $this->handleHttpError($response, $payload);

            return null;
        });
    }

    /**
     * Get diversified market data using multiple volume sampling
     */
    public function getDiversifiedMarketData(
        string $asset = 'USDT',
        string $fiat = 'VES',
        string $tradeType = 'BUY',
        array $volumeRanges = [100, 500, 1000, 2500, 5000]
    ): ?array {
        return $this->getP2PData($asset, $fiat, $tradeType, 1, 50, null, $volumeRanges);
    }

    /**
     * Get API health status
     */
    public function getHealthStatus(): array
    {
        $rateLimitRemaining = self::RATE_LIMIT_MAX_ATTEMPTS - RateLimiter::attempts(self::RATE_LIMIT_KEY);
        $rateLimitResetTime = RateLimiter::availableIn(self::RATE_LIMIT_KEY);

        return [
            'api_url' => self::API_URL,
            'rate_limit' => [
                'max_attempts' => self::RATE_LIMIT_MAX_ATTEMPTS,
                'remaining' => max(0, $rateLimitRemaining),
                'reset_in_seconds' => $rateLimitResetTime,
                'is_limited' => RateLimiter::tooManyAttempts(self::RATE_LIMIT_KEY, self::RATE_LIMIT_MAX_ATTEMPTS),
            ],
            'retry_config' => [
                'max_attempts' => self::RETRY_ATTEMPTS,
                'delay_ms' => self::RETRY_DELAY_MS,
            ],
        ];
    }
}
