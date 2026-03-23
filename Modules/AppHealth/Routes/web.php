<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\AppHealth\Http\Controllers\OperatorScorecardPageController;

Route::prefix('app-health')
    ->middleware(['web', 'auth', 'admin'])
    ->group(function (): void {
        Route::get('/scorecard', OperatorScorecardPageController::class)
            ->name('apphealth.operator.scorecard');
    });
