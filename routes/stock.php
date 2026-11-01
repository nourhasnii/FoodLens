<?php

/*
| Module Stocks & Optimisation (Triki Amine)
| À inclure à la fin de routes/web.php :   require __DIR__.'/stock.php';
*/

use App\Http\Controllers\ForecastController;
use App\Http\Controllers\OptimizationController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockMovementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active.account', 'professional', 'can:manage_stocks'])->group(function () {
    Route::resource('sites', SiteController::class)->except('show');

    Route::resource('stocks', StockController::class);

    Route::get('previsions', [ForecastController::class, 'index'])->name('forecast.index');

    Route::resource('stock-movements', StockMovementController::class)->only(['index', 'create', 'store']);

    Route::prefix('optimization')->name('optimization.')->controller(OptimizationController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{recommendation}', 'show')->name('show');
        Route::post('/{recommendation}/decision', 'decide')->name('decide');
        Route::post('/{recommendation}/rescue', 'rescue')->name('rescue');
        Route::get('/{recommendation}/donation/{site}', 'donation')->whereNumber('site')->name('donation');
    });
});
