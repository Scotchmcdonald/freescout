<?php

use Modules\PIB\Services\EntitlementEngineService;

it('EntitlementEngineService is resolvable from container', function () {
    $engine = app(EntitlementEngineService::class);
    expect($engine)->toBeInstanceOf(EntitlementEngineService::class);
});

it('EntitlementEngine alias binding works', function () {
    $engineAlias = app(\App\Services\EntitlementEngine::class);
    expect($engineAlias)->toBeInstanceOf(EntitlementEngineService::class);
});

it('EntitlementEngineService is singleton', function () {
    $e1 = app(EntitlementEngineService::class);
    $e2 = app(EntitlementEngineService::class);
    expect($e1)->toBe($e2);
});
