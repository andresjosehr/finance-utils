<?php

use App\Http\Controllers\BinanceP2PController;
use Illuminate\Support\Facades\Route;

Route::prefix('binance-p2p')->group(function () {
    Route::get('market-summary', [BinanceP2PController::class, 'getMarketSummary']);
    Route::get('buy-prices', [BinanceP2PController::class, 'getBuyPrices']);
    Route::get('sell-prices', [BinanceP2PController::class, 'getSellPrices']);
    Route::get('both-prices', [BinanceP2PController::class, 'getBothPrices']);
    Route::get('data', [BinanceP2PController::class, 'getP2PData']);
});