<?php

use App\Http\Controllers\Api\V1\LocationController;
use App\Http\Controllers\Api\V1\ProductionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->name('api.v1.')
    ->middleware('portal.api.bearer')
    ->group(function (): void {
        Route::get('locations', [LocationController::class, 'index'])->name('locations.index');
        Route::get('locations/{location}', [LocationController::class, 'show'])->name('locations.show');

        Route::get('productions', [ProductionController::class, 'index'])->name('productions.index');
        Route::get('productions/{production}', [ProductionController::class, 'show'])->name('productions.show');
    });
