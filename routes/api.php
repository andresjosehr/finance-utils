<?php

use App\Http\Controllers\BinanceP2PController;
use Illuminate\Support\Facades\Route;

Route::prefix('binance-p2p')->group(function () {
    // Basic P2P data endpoints
    Route::get('market-summary', [BinanceP2PController::class, 'getMarketSummary']);
    Route::get('buy-prices', [BinanceP2PController::class, 'getBuyPrices']);
    Route::get('sell-prices', [BinanceP2PController::class, 'getSellPrices']);
    Route::get('both-prices', [BinanceP2PController::class, 'getBothPrices']);
    Route::get('data', [BinanceP2PController::class, 'getP2PData']);
    Route::get('historical-prices', [BinanceP2PController::class, 'getHistoricalPrices']);
    
    // Advanced statistical analysis endpoints
    Route::get('statistical-analysis', [BinanceP2PController::class, 'getStatisticalAnalysis']);
    Route::get('comprehensive-analysis', [BinanceP2PController::class, 'getComprehensiveAnalysis']);
    Route::get('outliers', [BinanceP2PController::class, 'getOutliers']);
    Route::get('volatility-analysis', [BinanceP2PController::class, 'getVolatilityAnalysis']);
    Route::get('compare-outlier-methods', [BinanceP2PController::class, 'compareOutlierMethods']);
});