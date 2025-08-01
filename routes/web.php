<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::get('statistical-analysis', function () {
        return Inertia::render('statistical-analysis');
    })->name('statistical-analysis');

    Route::get('docs', function () {
        return Inertia::render('docs');
    })->name('docs');

    // Admin routes for trading pairs management
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('trading-pairs', \App\Http\Controllers\Admin\TradingPairController::class);
        Route::post('trading-pairs/{tradingPair}/toggle-active', [\App\Http\Controllers\Admin\TradingPairController::class, 'toggleActive'])
            ->name('trading-pairs.toggle-active');
        Route::post('trading-pairs/bulk-volume-sampling', [\App\Http\Controllers\Admin\TradingPairController::class, 'bulkUpdateVolumeSampling'])
            ->name('trading-pairs.bulk-volume-sampling');
        Route::get('trading-pairs-statistics', [\App\Http\Controllers\Admin\TradingPairController::class, 'statistics'])
            ->name('trading-pairs.statistics');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
