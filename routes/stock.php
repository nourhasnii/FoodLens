<?php

/*
| Module Stocks & Optimisation (Triki Amine)
| À inclure à la fin de routes/web.php :   require __DIR__.'/stock.php';
*/

use App\Http\Controllers\ForecastController;
use App\Http\Controllers\OptimizationController;
use App\Http\Controllers\ShelfLifeController;
use App\Http\Controllers\SimulationController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockMovementController;
use Illuminate\Support\Facades\Route;

// can:manage_stocks → admin, producteur, transformateur, distributeur (pas le consommateur)
Route::middleware(['auth', 'verified', 'active.account', 'can:manage_stocks'])->group(function () {
    Route::resource('sites', SiteController::class)->except('show');

    Route::resource('stocks', StockController::class);

    Route::get('previsions', [ForecastController::class, 'index'])->name('forecast.index');

    // Jumeau numérique « Et si ? » : simulation sans IA / avec IA (les stocks réels ne sont pas modifiés)
    Route::resource('simulations', SimulationController::class)->only(['index', 'store', 'show', 'destroy']);

    // DLC dynamique : vraie durée de vie des lots d'après les températures du transport (microbiologie prévisionnelle)
    Route::get('dlc-dynamique', [ShelfLifeController::class, 'index'])->name('shelf-life.index');
    Route::get('dlc-dynamique/{batch}', [ShelfLifeController::class, 'show'])->whereNumber('batch')->name('shelf-life.show');
    Route::post('dlc-dynamique/{batch}/decision', [ShelfLifeController::class, 'decide'])->whereNumber('batch')->name('shelf-life.decide');

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
