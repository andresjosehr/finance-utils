<?php

namespace App\Http\Controllers;

use App\Services\BinanceP2PService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BinanceP2PController extends Controller
{
    public function __construct(
        private BinanceP2PService $binanceP2PService
    ) {}

    public function getMarketSummary(Request $request): JsonResponse
    {
        $asset = $request->get('asset', 'USDT');
        $fiat = $request->get('fiat', 'VES');
        $rows = min((int) $request->get('rows', 20), 50); // Limitar máximo a 50

        $summary = $this->binanceP2PService->getMarketSummary($asset, $fiat, $rows);

        return response()->json($summary);
    }

    public function getBuyPrices(Request $request): JsonResponse
    {
        $asset = $request->get('asset', 'USDT');
        $fiat = $request->get('fiat', 'VES');
        $rows = min((int) $request->get('rows', 20), 50);

        $data = $this->binanceP2PService->getBuyPrices($asset, $fiat, $rows);

        if ($data === null) {
            return response()->json(['error' => 'Failed to fetch buy prices'], 500);
        }

        $metrics = $this->binanceP2PService->calculatePriceMetrics($data);

        return response()->json([
            'type' => 'buy',
            'asset' => $asset,
            'fiat' => $fiat,
            'metrics' => $metrics,
            'data' => $data,
        ]);
    }

    public function getSellPrices(Request $request): JsonResponse
    {
        $asset = $request->get('asset', 'USDT');
        $fiat = $request->get('fiat', 'VES');
        $rows = min((int) $request->get('rows', 20), 50);

        $data = $this->binanceP2PService->getSellPrices($asset, $fiat, $rows);

        if ($data === null) {
            return response()->json(['error' => 'Failed to fetch sell prices'], 500);
        }

        $metrics = $this->binanceP2PService->calculatePriceMetrics($data);

        return response()->json([
            'type' => 'sell',
            'asset' => $asset,
            'fiat' => $fiat,
            'metrics' => $metrics,
            'data' => $data,
        ]);
    }

    public function getBothPrices(Request $request): JsonResponse
    {
        $asset = $request->get('asset', 'USDT');
        $fiat = $request->get('fiat', 'VES');
        $rows = min((int) $request->get('rows', 20), 50);

        $data = $this->binanceP2PService->getBothPrices($asset, $fiat, $rows);

        $buyMetrics = $data['buy'] ? $this->binanceP2PService->calculatePriceMetrics($data['buy']) : null;
        $sellMetrics = $data['sell'] ? $this->binanceP2PService->calculatePriceMetrics($data['sell']) : null;

        return response()->json([
            'asset' => $asset,
            'fiat' => $fiat,
            'timestamp' => now()->toISOString(),
            'buy' => [
                'metrics' => $buyMetrics,
                'data' => $data['buy'],
            ],
            'sell' => [
                'metrics' => $sellMetrics,
                'data' => $data['sell'],
            ],
        ]);
    }

    public function getP2PData(Request $request): JsonResponse
    {
        $asset = $request->get('asset', 'USDT');
        $fiat = $request->get('fiat', 'VES');
        $tradeType = strtoupper($request->get('trade_type', 'BUY'));
        $page = max(1, (int) $request->get('page', 1));
        $rows = min((int) $request->get('rows', 20), 50);
        $transAmount = $request->get('trans_amount') ? (float) $request->get('trans_amount') : null;

        if (!in_array($tradeType, ['BUY', 'SELL'])) {
            return response()->json(['error' => 'Invalid trade_type. Must be BUY or SELL'], 400);
        }

        $data = $this->binanceP2PService->getP2PData($asset, $fiat, $tradeType, $page, $rows, $transAmount);

        if ($data === null) {
            return response()->json(['error' => 'Failed to fetch P2P data'], 500);
        }

        return response()->json($data);
    }
}