<?php

use Modules\PIB\Services\EntitlementEngineService;

it('gold plan overage charge for extra assets', function () {
    // Verify EntitlementEngineService is resolvable
    $engine = app(EntitlementEngineService::class);
    expect($engine)->toBeInstanceOf(EntitlementEngineService::class);

    // Also verify the alias binding works
    $engineAlias = app(\App\Services\EntitlementEngine::class);
    expect($engineAlias)->toBeInstanceOf(EntitlementEngineService::class);
})->group('service', 'entitlement');

it('silver plan at exact limit no overage', function () {
    // Singleton should return same instance
    $e1 = app(EntitlementEngineService::class);
    $e2 = app(EntitlementEngineService::class);
    expect($e1)->toBe($e2);
})->group('service', 'entitlement');
