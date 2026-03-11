<?php

use Illuminate\Support\Facades\Route;
use Modules\TreeScoutDeploymentManager\Http\Controllers\DashboardController;
use Modules\TreeScoutDeploymentManager\Http\Controllers\DeploymentController;
use Modules\TreeScoutDeploymentManager\Http\Controllers\ActivationController;
use Modules\TreeScoutDeploymentManager\Http\Controllers\SettingsController;

Route::prefix('deployment-manager')
    ->middleware(['auth', 'verified', 'can:view_tsdm'])
    ->group(function () {

        // Control Tower (Dashboard)
        Route::get('/', [DashboardController::class, 'index'])
            ->name('tsdm.dashboard');

        // ---------- Deployments ----------
        Route::prefix('deployments')->group(function () {
            Route::get('/', [DeploymentController::class, 'index'])
                ->name('tsdm.deployments.index');

            Route::get('/create', [DeploymentController::class, 'create'])
                ->name('tsdm.deployments.create')
                ->middleware('can:manage_tsdm');

            Route::post('/', [DeploymentController::class, 'store'])
                ->name('tsdm.deployments.store')
                ->middleware('can:manage_tsdm');

            Route::get('/{deployment}', [DeploymentController::class, 'show'])
                ->name('tsdm.deployments.show');

            Route::get('/{deployment}/edit', [DeploymentController::class, 'edit'])
                ->name('tsdm.deployments.edit')
                ->middleware('can:manage_tsdm');

            Route::put('/{deployment}', [DeploymentController::class, 'update'])
                ->name('tsdm.deployments.update')
                ->middleware('can:manage_tsdm');

            Route::post('/{deployment}/revoke', [DeploymentController::class, 'revoke'])
                ->name('tsdm.deployments.revoke')
                ->middleware('can:manage_tsdm');

            Route::post('/{deployment}/reinstate', [DeploymentController::class, 'reinstate'])
                ->name('tsdm.deployments.reinstate')
                ->middleware('can:manage_tsdm');
        });

        // ---------- Activations ----------
        Route::prefix('activations')->group(function () {
            // Readable by anyone with view_tsdm
            Route::get('/', [ActivationController::class, 'index'])
                ->name('tsdm.activations.index');

            // Write actions require the elevated permission
            Route::post('/', [ActivationController::class, 'store'])
                ->name('tsdm.activations.store')
                ->middleware('can:issue_tsdm_activations');

            Route::delete('/{activation}/expire', [ActivationController::class, 'expire'])
                ->name('tsdm.activations.expire')
                ->middleware('can:issue_tsdm_activations');
        });

        // ---------- Settings ----------
        Route::prefix('settings')
            ->group(function () {
                Route::get('/', [SettingsController::class, 'index'])
                    ->name('tsdm.settings.index')
                    ->middleware('can:view_tsdm_settings');
                Route::post('/', [SettingsController::class, 'update'])
                    ->name('tsdm.settings.update')
                    ->middleware('can:manage_tsdm_settings');
            });
    });
