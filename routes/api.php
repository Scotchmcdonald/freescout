<?php

use App\Http\Controllers\Webhooks\Action1WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| Webhook Routes
|--------------------------------------------------------------------------
|
| Webhook endpoints for external service integrations.
| 
| Security:
| - VerifyWebhookSignature middleware validates signatures
| - Rate limiting per source (60/minute)
| - HTTPS enforced in production
| - IP whitelist checking
| - All webhook activity logged to security channel
|
*/

// Google Workspace Webhooks - Moved to Modules/GoogleAdmin/Routes/api.php

// Action1 RMM Webhooks
Route::prefix('webhooks/action1')->group(function () {
    Route::post('/devices', [Action1WebhookController::class, 'devices'])
        ->middleware(['webhook.verify:action1'])
        ->middleware('throttle:action1_webhooks')
        ->name('webhooks.action1.devices');

    Route::post('/policies', [Action1WebhookController::class, 'policies'])
        ->middleware(['webhook.verify:action1'])
        ->middleware('throttle:action1_webhooks')
        ->name('webhooks.action1.policies');

    Route::post('/alerts', [Action1WebhookController::class, 'alerts'])
        ->middleware(['webhook.verify:action1'])
        ->middleware('throttle:action1_webhooks')
        ->name('webhooks.action1.alerts');
});
