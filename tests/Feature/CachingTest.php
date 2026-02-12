<?php

use App\Services\CacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Crm\Models\Client;
use Modules\PIB\Events\InvoicePaid;
use Modules\PIB\Models\Invoice;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

test('cache service builds standardized keys', function () {
    $cacheService = app(CacheService::class);
    
    $cacheService->put(
        'billing',
        'entitlement',
        123,
        'current',
        ['test' => 'data'],
        60
    );

    expect(Cache::has('billing:entitlement:123:current'))->toBeTrue();
});

test('cache service remembers values', function () {
    $cacheService = app(CacheService::class);
    $callCount = 0;
    
    $result1 = $cacheService->remember(
        'billing',
        'entitlement',
        123,
        'current',
        60,
        function () use (&$callCount) {
            $callCount++;
            return ['entitlement' => 'data'];
        }
    );

    $result2 = $cacheService->remember(
        'billing',
        'entitlement',
        123,
        'current',
        60,
        function () use (&$callCount) {
            $callCount++;
            return ['entitlement' => 'data'];
        }
    );

    expect($result1)->toEqual($result2);
    expect($callCount)->toBe(1, 'Callback should only be called once (cache hit on second call)');
});

test('cache service forgets values', function () {
    $cacheService = app(CacheService::class);
    
    $cacheService->put('billing', 'entitlement', 123, 'current', ['test' => 'data'], 60);
    expect($cacheService->has('billing', 'entitlement', 123, 'current'))->toBeTrue();

    $cacheService->forget('billing', 'entitlement', 123, 'current');
    expect($cacheService->has('billing', 'entitlement', 123, 'current'))->toBeFalse();
});

test('cache service gets values', function () {
    $cacheService = app(CacheService::class);
    
    $cacheService->put('billing', 'entitlement', 123, 'current', ['test' => 'data'], 60);

    $value = $cacheService->get('billing', 'entitlement', 123, 'current');
    expect($value)->toEqual(['test' => 'data']);
});

test('cache service returns default for missing keys', function () {
    $cacheService = app(CacheService::class);
    
    $value = $cacheService->get('billing', 'entitlement', 999, 'current', 'default-value');
    expect($value)->toBe('default-value');
});

test('cache service warms multiple entities', function () {
    $cacheService = app(CacheService::class);
    $entityIds = [1, 2, 3, 4, 5];
    
    $warmed = $cacheService->warmMultiple(
        'billing',
        'entitlement',
        $entityIds,
        'current',
        60,
        function ($entityId) {
            return ['entity_id' => $entityId, 'data' => "data-{$entityId}"];
        }
    );

    expect($warmed)->toBe(5);

    // Verify all were cached
    foreach ($entityIds as $id) {
        expect($cacheService->has('billing', 'entitlement', $id, 'current'))->toBeTrue();
    }
});

test('cache ttl constants are defined', function () {
    expect(CacheService::TTL_USER_PERMISSIONS)->toBe(86400);
    expect(CacheService::TTL_CLIENT_ENTITLEMENTS)->toBe(300);
    expect(CacheService::TTL_CREDIT_BALANCE)->toBe(60);
    expect(CacheService::TTL_ASSET_COUNT)->toBe(300);
    expect(CacheService::TTL_RATE_LIMITER)->toBe(3600);
});

test('cache key format is consistent', function () {
    $cacheService = app(CacheService::class);
    
    // Test with attribute
    $cacheService->put('billing', 'entitlement', 123, 'current', 'value1', 60);
    expect(Cache::has('billing:entitlement:123:current'))->toBeTrue();

    // Test without attribute
    $cacheService->put('crm', 'client', 456, null, 'value2', 60);
    expect(Cache::has('crm:client:456'))->toBeTrue();
});

test('cache service handles string entity ids', function () {
    $cacheService = app(CacheService::class);
    
    $cacheService->put('auth', 'session', 'abc123', 'token', 'token-value', 60);
    expect($cacheService->has('auth', 'session', 'abc123', 'token'))->toBeTrue();
    
    $value = $cacheService->get('auth', 'session', 'abc123', 'token');
    expect($value)->toBe('token-value');
});

test('cache warming command runs successfully', function () {
    // Create some active clients
    Client::factory()->count(5)->create(['status' => 'active']);
    Client::factory()->count(3)->create(['status' => 'inactive']);

    $this->artisan('cache:warm', ['--clients' => 3])
        ->expectsOutput('🔥 Warming cache...')
        ->assertExitCode(0);
});
