<?php

use Illuminate\Support\Facades\Route;
use Modules\TreeScoutDeploymentManager\Http\Controllers\Api\ActivationBrokerController;

/*
|--------------------------------------------------------------------------
| Public API Route — no auth middleware.
| The OTAC itself IS the credential; it is single-use and short-lived.
|--------------------------------------------------------------------------
|
| Endpoint consumed by the client-side deploy.sh script.
*/

Route::post('/tsdm/activate', [ActivationBrokerController::class, 'activate'])
    ->name('tsdm.api.activate')
    ->middleware('throttle:10,1');  // 10 attempts per minute per IP
