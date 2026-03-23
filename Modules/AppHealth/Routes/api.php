<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\AppHealth\Http\Controllers\HealthController;
use Modules\AppHealth\Http\Controllers\MetricsController;
use Modules\AppHealth\Http\Controllers\ScalingScorecardController;

Route::prefix('internal')
    ->middleware(['apphealth.internal'])
    ->group(function (): void {
        Route::get('/health', [HealthController::class, 'basic'])->name('apphealth.health.basic');
        Route::get('/health/detailed', [HealthController::class, 'detailed'])->name('apphealth.health.detailed');

        Route::get('/metrics', MetricsController::class)
            ->name('apphealth.metrics');

        Route::get('/scaling/scorecard', ScalingScorecardController::class)
            ->middleware(['auth', 'admin'])
            ->name('apphealth.scaling.scorecard');
    });
