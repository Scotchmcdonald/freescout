<?php

use Modules\Crm\Models\Client;
use Modules\SoftwareSubscriptions\Exceptions\LicenseLimitExceededException;
use Modules\SoftwareSubscriptions\Models\ClientSoftwareSubscription;
use Modules\SoftwareSubscriptions\Models\SoftwareAssignment;
use Modules\SoftwareSubscriptions\Models\SoftwareProduct;
use Modules\SoftwareSubscriptions\Services\SubscriptionCounterService;

// Helper functions
function createTestSubscription($client, $product, int $purchasedQuantity = 5)
{
    // Delete any existing subscription for this client/product combination
    ClientSoftwareSubscription::where('client_id', $client->id)
        ->where('software_product_id', $product->id)
        ->forceDelete();

    return ClientSoftwareSubscription::create([
        'client_id' => $client->id,
        'software_product_id' => $product->id,
        'billing_behavior' => 'passthrough',
        'purchased_quantity' => $purchasedQuantity,
        'assigned_count' => 0,
        'minimum_quantity' => 0,
        'status' => 'active',
    ]);
}

function createMockAssignable($client)
{
    if (class_exists(\Modules\Crm\Models\Contact::class)) {
        return \Modules\Crm\Models\Contact::firstOrCreate(
            ['email' => 'test-'.uniqid().'@example.com'],
            [
                'first_name' => 'Test',
                'last_name' => 'User',
                'client_id' => $client->id,
                'status' => 'active',
            ]
        );
    }
    $mock = new \stdClass;
    $mock->id = rand(1000, 9999);
    $mock->name = 'Test Assignable '.$mock->id;

    return $mock;
}

beforeEach(function () {
    if (! class_exists(Client::class) || ! class_exists(SoftwareProduct::class)) {
        $this->markTestSkipped('SoftwareSubscriptions or CRM module not installed.');
    }

    $this->testClient = Client::firstOrCreate(
        ['name' => 'Atomic Counter Test Client'],
        ['status' => 'active']
    );

    $this->testProduct = SoftwareProduct::firstOrCreate(
        ['name' => 'Test Software Product'],
        [
            'vendor' => 'Test Vendor',
            'category' => 'productivity',
            'licensing_model' => 'per_user',
            'vendor_cost' => 10.00,
            'default_price' => 15.00,
            'is_active' => true,
        ]
    );
});

test('concurrent software assignments', function () {
    $subscription = createTestSubscription($this->testClient, $this->testProduct, 10);
    $counterService = app(SubscriptionCounterService::class);

    $assignables = [];
    for ($i = 0; $i < 10; $i++) {
        $assignables[] = createMockAssignable($this->testClient);
    }

    $results = [];
    foreach ($assignables as $assignable) {
        try {
            $assignment = $counterService->assignLicense($subscription, $assignable);
            $results[] = ['success' => true, 'assignment_id' => $assignment->id];
        } catch (LicenseLimitExceededException $e) {
            $results[] = ['success' => false, 'error' => 'license_limit'];
        } catch (\Exception $e) {
            $results[] = ['success' => false, 'error' => $e->getMessage()];
        }
    }

    $subscription->refresh();

    $successCount = count(array_filter($results, fn ($r) => $r['success']));
    expect($successCount)->toBe(10);
    expect($subscription->assigned_count)->toBe(10);
    expect($subscription->activeAssignments()->count())->toBe(10);

    $subscription->forceDelete();
});

test('counter rollback on failure', function () {
    $subscription = createTestSubscription($this->testClient, $this->testProduct, 2);
    $counterService = app(SubscriptionCounterService::class);

    $assignable1 = createMockAssignable($this->testClient);
    $assignable2 = createMockAssignable($this->testClient);

    $counterService->assignLicense($subscription, $assignable1);
    $counterService->assignLicense($subscription, $assignable2);

    $subscription->refresh();
    expect($subscription->assigned_count)->toBe(2);

    $assignable3 = createMockAssignable($this->testClient);

    $exceptionThrown = false;
    try {
        $counterService->assignLicense($subscription, $assignable3);
    } catch (LicenseLimitExceededException $e) {
        $exceptionThrown = true;
        expect($e->subscriptionId)->toBe($subscription->id);
        expect($e->purchasedQuantity)->toBe(2);
    }

    expect($exceptionThrown)->toBeTrue();

    $subscription->refresh();
    expect($subscription->assigned_count)->toBe(2); // Should still be 2

    $subscription->forceDelete();
});

test('counter drives billing calculation', function () {
    $subscription = createTestSubscription($this->testClient, $this->testProduct, 10);
    $counterService = app(SubscriptionCounterService::class);

    expect($subscription->calculateTotalCost())->toBe(0.00);

    for ($i = 0; $i < 3; $i++) {
        $counterService->assignLicense($subscription, createMockAssignable($this->testClient));
    }

    $subscription->refresh();

    $expectedCost = 3 * $this->testProduct->vendor_cost;
    expect($subscription->calculateTotalCost())->toBe((float) $expectedCost);
    expect($subscription->assigned_count)->toBe(3);

    $subscription->forceDelete();
});

test('counter decrement on removal', function () {
    $subscription = createTestSubscription($this->testClient, $this->testProduct, 5);
    $counterService = app(SubscriptionCounterService::class);

    $assignments = [];
    for ($i = 0; $i < 3; $i++) {
        $assignments[] = $counterService->assignLicense($subscription, createMockAssignable($this->testClient));
    }

    $subscription->refresh();
    expect($subscription->assigned_count)->toBe(3);

    $counterService->revokeLicense($assignments[0], SoftwareAssignment::REVOKED_MANUAL);

    $subscription->refresh();
    expect($subscription->assigned_count)->toBe(2);

    $actualCount = $subscription->activeAssignments()->count();
    expect($actualCount)->toBe($subscription->assigned_count);

    $subscription->forceDelete();
});

test('counter reconciliation detects drift', function () {
    $subscription = createTestSubscription($this->testClient, $this->testProduct, 10);
    $counterService = app(SubscriptionCounterService::class);

    for ($i = 0; $i < 3; $i++) {
        $counterService->assignLicense($subscription, createMockAssignable($this->testClient));
    }

    $subscription->refresh();
    expect($subscription->assigned_count)->toBe(3);

    // Simulate drift
    $subscription->update(['assigned_count' => 5]);
    $subscription->refresh();
    expect($subscription->assigned_count)->toBe(5);

    $reconciledCount = $counterService->reconcileCount($subscription);

    expect($reconciledCount)->toBe(3);

    $subscription->refresh();
    expect($subscription->assigned_count)->toBe(3);

    $subscription->forceDelete();
});

test('license limit enforced', function () {
    $subscription = createTestSubscription($this->testClient, $this->testProduct, 2);
    $counterService = app(SubscriptionCounterService::class);

    $counterService->assignLicense($subscription, createMockAssignable($this->testClient));
    $counterService->assignLicense($subscription, createMockAssignable($this->testClient));

    $subscription->refresh();
    expect($subscription->assigned_count)->toBe(2);
    expect($subscription->hasAvailableLicenses())->toBeFalse();

    expect(fn () => $counterService->assignLicense($subscription, createMockAssignable($this->testClient)))
        ->toThrow(LicenseLimitExceededException::class);

    $subscription->forceDelete();
});

test('unlimited licenses work', function () {
    // purchased_quantity = 0 means unlimited
    $subscription = createTestSubscription($this->testClient, $this->testProduct, 0);
    $counterService = app(SubscriptionCounterService::class);

    for ($i = 0; $i < 20; $i++) {
        $counterService->assignLicense($subscription, createMockAssignable($this->testClient));
    }

    $subscription->refresh();
    expect($subscription->assigned_count)->toBe(20);
    expect($subscription->hasAvailableLicenses())->toBeTrue();

    $subscription->forceDelete();
});
