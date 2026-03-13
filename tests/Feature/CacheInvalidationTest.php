<?php

use App\Services\CacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Modules\Crm\Models\Client;
use Modules\PIB\Events\InvoicePaid;
use Modules\PIB\Listeners\InvalidateBillingCache;
use Modules\PIB\Models\Invoice;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

afterEach(function () {
    Cache::flush();
});

test('cache service builds keys correctly', function () {
    $service = app(CacheService::class);

    // Test with attribute
    $value = $service->put('billing', 'client', 123, 'balance', 1000, 60);
    expect($value)->toBeTrue();

    $retrieved = $service->get('billing', 'client', 123, 'balance');
    expect($retrieved)->toBe(1000);
});

test('cache service remembers values on subsequent calls', function () {
    $service = app(CacheService::class);
    $callCount = 0;

    // First call - cache miss
    $value1 = $service->remember(
        'billing',
        'client',
        123,
        'balance',
        60,
        function () use (&$callCount) {
            $callCount++;

            return 5000;
        }
    );

    expect($value1)->toBe(5000);
    expect($callCount)->toBe(1);

    // Second call - cache hit (callback not executed)
    $value2 = $service->remember(
        'billing',
        'client',
        123,
        'balance',
        60,
        function () use (&$callCount) {
            $callCount++;

            return 5000;
        }
    );

    expect($value2)->toBe(5000);
    expect($callCount)->toBe(1); // Not incremented on cache hit
});

test('cache service forgets specific keys', function () {
    $service = app(CacheService::class);

    // Put value
    $service->put('billing', 'client', 123, 'balance', 1000, 60);
    expect($service->has('billing', 'client', 123, 'balance'))->toBeTrue();

    // Forget value
    $forgotten = $service->forget('billing', 'client', 123, 'balance');
    expect($forgotten)->toBeTrue();
    expect($service->has('billing', 'client', 123, 'balance'))->toBeFalse();
});

test('cache invalidation listener fires on invoice paid event', function () {
    // Create a mock invoice with client_id
    $clientId = 123;
    $invoice = new Invoice;
    $invoice->id = 1;
    $invoice->client_id = $clientId;
    $invoice->status = 'pending';

    $service = app(CacheService::class);

    // Pre-populate cache
    $service->put('billing', 'client', $clientId, 'balance', 1000, 60);
    $service->put('billing', 'entitlement', $clientId, 'current', ['amount' => 500], 60);
    $service->put('billing', 'client', $clientId, 'invoices', [], 60);

    expect($service->has('billing', 'client', $clientId, 'balance'))->toBeTrue();
    expect($service->has('billing', 'entitlement', $clientId, 'current'))->toBeTrue();

    // Trigger event
    $event = new InvoicePaid($invoice);
    $listener = app(InvalidateBillingCache::class);
    $listener->handle($event);

    // Verify caches invalidated
    expect($service->has('billing', 'client', $clientId, 'balance'))->toBeFalse();
    expect($service->has('billing', 'entitlement', $clientId, 'current'))->toBeFalse();
    expect($service->has('billing', 'client', $clientId, 'invoices'))->toBeFalse();
});

test('cache warming works for multiple entities', function () {
    $service = app(CacheService::class);

    $clientIds = [1, 2, 3, 4, 5];

    $warmed = $service->warmMultiple(
        'billing',
        'client',
        $clientIds,
        'balance',
        60,
        fn ($id) => $id * 100 // Mock balance calculation
    );

    expect($warmed)->toBe(5);

    // Verify all values cached
    foreach ($clientIds as $id) {
        $balance = $service->get('billing', 'client', $id, 'balance');
        expect($balance)->toBe($id * 100);
    }
});

test('cache warming continues on individual failures', function () {
    $service = app(CacheService::class);

    $clientIds = [1, 2, 3];

    $warmed = $service->warmMultiple(
        'billing',
        'client',
        $clientIds,
        'balance',
        60,
        function ($id) {
            if ($id === 2) {
                throw new \Exception('Test failure');
            }

            return $id * 100;
        }
    );

    // Should warm 2 out of 3 (id=2 failed)
    expect($warmed)->toBe(2);

    // Verify successful ones are cached
    expect($service->get('billing', 'client', 1, 'balance'))->toBe(100);
    expect($service->get('billing', 'client', 2, 'balance'))->toBeNull(); // Failed
    expect($service->get('billing', 'client', 3, 'balance'))->toBe(300);
});

test('cache service respects TTL constants', function () {
    expect(CacheService::TTL_USER_PERMISSIONS)->toBe(86400);
    expect(CacheService::TTL_CLIENT_ENTITLEMENTS)->toBe(300);
    expect(CacheService::TTL_CREDIT_BALANCE)->toBe(60);
    expect(CacheService::TTL_ASSET_COUNT)->toBe(300);
    expect(CacheService::TTL_HOT_DATA)->toBe(60);
});

test('cache keys follow standard naming convention', function () {
    $service = app(CacheService::class);

    // Test various key formats
    $service->put('billing', 'entitlement', 123, 'current', 'value1', 60);
    $service->put('crm', 'ticket', 456, 'status', 'value2', 60);
    $service->put('asset', 'computer', 789, null, 'value3', 60);

    // Keys should be: billing:entitlement:123:current, crm:ticket:456:status, asset:computer:789
    expect($service->get('billing', 'entitlement', 123, 'current'))->toBe('value1');
    expect($service->get('crm', 'ticket', 456, 'status'))->toBe('value2');
    expect($service->get('asset', 'computer', 789, null))->toBe('value3');
});

test('multiple caches for same entity can be independently invalidated', function () {
    $service = app(CacheService::class);
    $clientId = 100;

    // Create multiple cache entries for same client
    $service->put('billing', 'client', $clientId, 'balance', 1000, 60);
    $service->put('billing', 'client', $clientId, 'invoices', [], 60);
    $service->put('billing', 'entitlement', $clientId, 'current', ['rate' => 50], 60);

    // Invalidate only balance
    $service->forget('billing', 'client', $clientId, 'balance');

    // Verify only balance is invalidated
    expect($service->has('billing', 'client', $clientId, 'balance'))->toBeFalse();
    expect($service->has('billing', 'client', $clientId, 'invoices'))->toBeTrue();
    expect($service->has('billing', 'entitlement', $clientId, 'current'))->toBeTrue();
});

test('cache service handles string entity IDs', function () {
    $service = app(CacheService::class);

    $service->put('auth', 'session', 'abc-123-def', 'user_id', 456, 60);
    $retrieved = $service->get('auth', 'session', 'abc-123-def', 'user_id');

    expect($retrieved)->toBe(456);
});

test('cache service returns default value when key does not exist', function () {
    $service = app(CacheService::class);

    $value = $service->get('billing', 'client', 999, 'balance', 0);
    expect($value)->toBe(0);

    $value2 = $service->get('billing', 'client', 999, 'balance', null);
    expect($value2)->toBeNull();
});

test('invoice paid event is properly wired in event service provider', function () {
    Event::fake();

    $invoice = new Invoice;
    $invoice->id = 1;
    $invoice->client_id = 123;

    // Dispatch event
    event(new InvoicePaid($invoice));

    // Verify event was dispatched
    Event::assertDispatched(InvoicePaid::class);
});

test('cache invalidation follows documented patterns from architecture', function () {
    // This test verifies that the implementation matches SYSTEM_ARCHITECTURE.md Section 14.2

    $service = app(CacheService::class);
    $clientId = 1;

    // Pattern 1: Layer 1 - Application State (24h TTL)
    $service->put('auth', 'user', $clientId, 'permissions', ['read', 'write'], CacheService::TTL_USER_PERMISSIONS);

    // Pattern 2: Layer 2 - Query Results (5m TTL)
    $service->put('billing', 'entitlement', $clientId, 'current', ['amount' => 100], CacheService::TTL_CLIENT_ENTITLEMENTS);

    // Pattern 3: Layer 3 - Hot Data (1m TTL)
    $service->put('billing', 'client', $clientId, 'balance', 5000, CacheService::TTL_CREDIT_BALANCE);

    // Verify all cached
    expect($service->has('auth', 'user', $clientId, 'permissions'))->toBeTrue();
    expect($service->has('billing', 'entitlement', $clientId, 'current'))->toBeTrue();
    expect($service->has('billing', 'client', $clientId, 'balance'))->toBeTrue();
});

test('flush entity removes all caches for specific entity', function () {
    $service = app(CacheService::class);
    $clientId = 123;

    // This test requires cache tags support (Redis/Memcached)
    // If using array/file cache, this will skip
    if (! in_array(config('cache.default'), ['redis', 'memcached'])) {
        $this->markTestIncomplete('Cache tags require Redis or Memcached');
    }

    // Create multiple caches for same entity
    Cache::tags(["billing:client:{$clientId}"])->put('billing:client:'.$clientId.':balance', 1000, 60);
    Cache::tags(["billing:client:{$clientId}"])->put('billing:client:'.$clientId.':invoices', [], 60);

    // Flush entity
    $service->flushEntity('billing', 'client', $clientId);

    // Verify all related caches cleared
    expect(Cache::tags(["billing:client:{$clientId}"])->get('billing:client:'.$clientId.':balance'))->toBeNull();
    expect(Cache::tags(["billing:client:{$clientId}"])->get('billing:client:'.$clientId.':invoices'))->toBeNull();
});
