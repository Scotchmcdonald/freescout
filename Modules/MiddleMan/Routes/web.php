<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\MiddleMan\Http\Controllers\DashboardController;
use Modules\MiddleMan\Http\Controllers\AdvancedController;
use Modules\MiddleMan\Http\Controllers\InterceptController;
use Modules\MiddleMan\Http\Controllers\LoggingController;
use Modules\MiddleMan\Http\Controllers\MarshalController;
use Modules\MiddleMan\Http\Controllers\MutingController;
use Modules\MiddleMan\Http\Controllers\ReplayController;

Route::prefix('middleman')->middleware(['auth', 'verified', 'can:view_middleman'])->group(function () {

    // Dashboard — "Flight Deck"
    Route::get('/', [DashboardController::class, 'index'])->name('middleman.dashboard');
    Route::post('/circuit-breaker/reset', [DashboardController::class, 'resetCircuitBreaker'])
        ->middleware('can:manage_middleman')
        ->name('middleman.circuit-breaker.reset');

    /*
    |----------------------------------------------------------------------
    | Logging
    |----------------------------------------------------------------------
    */
    Route::prefix('logging')->group(function () {
        Route::get('/', [LoggingController::class, 'index'])->name('middleman.logging.index');
        Route::get('/filter', [LoggingController::class, 'filter'])->name('middleman.logging.filter');
        Route::get('/{id}', [LoggingController::class, 'show'])->name('middleman.logging.show');

        Route::middleware('can:manage_middleman')->group(function () {
            Route::post('/toggle', [LoggingController::class, 'toggle'])->name('middleman.logging.toggle');
            Route::post('/rules', [LoggingController::class, 'addRule'])->name('middleman.logging.rules.add');
            Route::delete('/rules', [LoggingController::class, 'removeRule'])->name('middleman.logging.rules.remove');
            Route::delete('/clear', [LoggingController::class, 'clear'])->name('middleman.logging.clear');
        });
    });

    /*
    |----------------------------------------------------------------------
    | Interception
    |----------------------------------------------------------------------
    */
    Route::prefix('intercept')->group(function () {
        Route::get('/', [InterceptController::class, 'index'])->name('middleman.intercept.index');
        Route::get('/{id}', [InterceptController::class, 'show'])->name('middleman.intercept.show');

        Route::middleware('can:manage_middleman')->group(function () {
            Route::post('/toggle', [InterceptController::class, 'toggle'])->name('middleman.intercept.toggle');
            Route::post('/rules', [InterceptController::class, 'addRule'])->name('middleman.intercept.rules.add');
            Route::delete('/rules', [InterceptController::class, 'removeRule'])->name('middleman.intercept.rules.remove');

            Route::put('/{id}/payload', [InterceptController::class, 'updatePayload'])->name('middleman.intercept.payload.update');
            Route::post('/{id}/fire', [InterceptController::class, 'fire'])->name('middleman.intercept.fire');
            Route::post('/{id}/discard', [InterceptController::class, 'discard'])->name('middleman.intercept.discard');

            Route::post('/fire-selected', [InterceptController::class, 'fireSelected'])->name('middleman.intercept.fire-selected');
            Route::post('/fire-all', [InterceptController::class, 'fireAll'])->name('middleman.intercept.fire-all');
            Route::post('/reorder', [InterceptController::class, 'reorder'])->name('middleman.intercept.reorder');
        });
    });

    /*
    |----------------------------------------------------------------------
    | Marshalling
    |----------------------------------------------------------------------
    */
    Route::prefix('marshal')->group(function () {
        Route::get('/', [MarshalController::class, 'index'])->name('middleman.marshal.index');
        Route::get('/parameters', [MarshalController::class, 'parameters'])->name('middleman.marshal.parameters');
        Route::get('/search-model', [MarshalController::class, 'searchModel'])->name('middleman.marshal.search-model');

        Route::middleware('can:manage_middleman')->group(function () {
            Route::post('/fire', [MarshalController::class, 'fire'])->name('middleman.marshal.fire');
            Route::post('/batch', [MarshalController::class, 'batch'])->name('middleman.marshal.batch');
            Route::post('/presets', [MarshalController::class, 'savePreset'])->name('middleman.marshal.presets.save');
            Route::delete('/presets/{id}', [MarshalController::class, 'deletePreset'])->name('middleman.marshal.presets.delete');
        });
    });

    /*
    |----------------------------------------------------------------------
    | Listener Muting (Surgical)
    |----------------------------------------------------------------------
    */
    Route::prefix('muting')->group(function () {
        Route::get('/', [MutingController::class, 'index'])->name('middleman.muting.index');
        Route::get('/data', [MutingController::class, 'data'])->name('middleman.muting.data');

        Route::middleware('can:manage_middleman')->group(function () {
            Route::post('/add', [MutingController::class, 'addMute'])->name('middleman.muting.add');
            Route::delete('/remove', [MutingController::class, 'removeMute'])->name('middleman.muting.remove');
            Route::delete('/clear', [MutingController::class, 'clearAll'])->name('middleman.muting.clear');
        });
    });

    /*
    |----------------------------------------------------------------------
    | Advanced Features
    |----------------------------------------------------------------------
    */
    Route::prefix('topology')->group(function () {
        Route::get('/', [AdvancedController::class, 'topology'])->name('middleman.topology.index');
        Route::get('/diagram.svg', [AdvancedController::class, 'topologyDiagram'])->name('middleman.topology.diagram');
    });

    Route::prefix('schema')->group(function () {
        Route::get('/', [AdvancedController::class, 'schema'])->name('middleman.schema.index');
    });

    Route::prefix('tracing')->group(function () {
        Route::get('/', [AdvancedController::class, 'tracing'])->name('middleman.tracing.index');
    });

    /*
    |----------------------------------------------------------------------
    | Historical Replay
    |----------------------------------------------------------------------
    */
    Route::prefix('replay')->group(function () {
        Route::get('/', [AdvancedController::class, 'replay'])->name('middleman.replay.index');

        Route::middleware('can:manage_middleman')->group(function () {
            Route::post('/sequence', [ReplayController::class, 'replaySequence'])->name('middleman.replay.sequence');
            Route::post('/{logId}', [ReplayController::class, 'replay'])->name('middleman.replay');
        });
    });
});
