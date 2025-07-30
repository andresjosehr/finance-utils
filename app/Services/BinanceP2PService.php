<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BinanceP2PService
{
    private const API_URL = 'https://p2p.binance.com/bapi/c2c/v2/friendly/c2c/adv/search';
    
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

    public function getP2PData(
        string $asset = 'USDT',
        string $fiat = 'VES',
        string $tradeType = 'BUY',
        int $page = 1,
        int $rows = 50,
        ?float $transAmount = null
    ): ?array {
        $payload = [
            'fiat' => $fiat,
            'page' => $page,
            'rows' => $rows,
            'tradeType' => $tradeType,
            'asset' => $asset,
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

        if ($transAmount !== null) {
            $payload['transAmount'] = $transAmount;
        }

        try {
            $response = Http::withHeaders($this->defaultHeaders)
                ->timeout(30)
                ->post(self::API_URL, $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Binance P2P API request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'payload' => $payload,
            ]);

            return null;
        } catch (RequestException $e) {
            Log::error('Binance P2P API request exception', [
                'message' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return null;
        }
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
}