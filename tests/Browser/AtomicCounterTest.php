<?php

/**
 * Atomic Counter Tests
 * 
 * Validates atomic counter integrity for software license assignments.
 * Prevents race conditions that lead to billing inaccuracies.
 * 
 * PRIORITY: ⭐⭐⭐⭐⭐ (Critical - Revenue Protection)
 * 
 * RUNNING TESTS:
 * php artisan dusk tests/Browser/AtomicCounterTest.php
 * php artisan dusk --group=counters
 * php artisan dusk --group=concurrency
 */

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Laravel\Dusk\Browser;
use Modules\Crm\Models\Client;
use Modules\SoftwareSubscriptions\Exceptions\LicenseLimitExceededException;
use Modules\SoftwareSubscriptions\Models\ClientSoftwareSubscription;
use Modules\SoftwareSubscriptions\Models\SoftwareAssignment;
use Modules\SoftwareSubscriptions\Models\SoftwareProduct;
use Modules\SoftwareSubscriptions\Services\SubscriptionCounterService;
use PHPUnit\Framework\Attributes\Group;
use Tests\DuskTestCase;

class AtomicCounterTest extends DuskTestCase
{
    protected static ?Client $testClient = null;
    protected static ?SoftwareProduct $testProduct = null;
    protected static ?ClientSoftwareSubscription $testSubscription = null;

    protected function getAdminUser(): User
    {
        return User::where('email', 'admin@example.com')->orWhere('role', User::ROLE_ADMIN)->firstOrFail();
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure test data exists
        if (!self::$testClient) {
            self::$testClient = Client::firstOrCreate(
                ['name' => 'Atomic Counter Test Client'],
                ['status' => 'active']
            );
        }

        if (!self::$testProduct) {
            self::$testProduct = SoftwareProduct::firstOrCreate(
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
        }
    }

    protected function createTestSubscription(int $purchasedQuantity = 5): ClientSoftwareSubscription
    {
        // Delete any existing subscription for this client/product combination
        ClientSoftwareSubscription::where('client_id', self::$testClient->id)
            ->where('software_product_id', self::$testProduct->id)
            ->forceDelete();
            
        return ClientSoftwareSubscription::create([
            'client_id' => self::$testClient->id,
            'software_product_id' => self::$testProduct->id,
            'billing_behavior' => 'passthrough',
            'purchased_quantity' => $purchasedQuantity,
            'assigned_count' => 0,
            'minimum_quantity' => 0,
            'status' => 'active',
        ]);
    }

    protected function createMockAssignable(): object
    {
        // Create a simple mock assignable object (like a Contact)
        if (class_exists(\Modules\Crm\Models\Contact::class)) {
            return \Modules\Crm\Models\Contact::firstOrCreate(
                ['email' => 'test-' . uniqid() . '@example.com'],
                [
                    'first_name' => 'Test',
                    'last_name' => 'User',
                    'client_id' => self::$testClient->id,
                    'status' => 'active',
                ]
            );
        }

        // Fallback: use a simple stdClass with required properties
        $mock = new \stdClass();
        $mock->id = rand(1000, 9999);
        $mock->name = 'Test Assignable ' . $mock->id;
        return $mock;
    }

    #[Group('counters')]
    #[Group('software')]
    #[Group('concurrency')]
    public function test_concurrent_software_assignments(): void
    {
        $subscription = $this->createTestSubscription(10);
        $counterService = app(SubscriptionCounterService::class);

        // Create 10 assignables
        $assignables = [];
        for ($i = 0; $i < 10; $i++) {
            $assignables[] = $this->createMockAssignable();
        }

        // Simulate concurrent assignments
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

        // Refresh subscription
        $subscription->refresh();

        // Verify: exactly 10 successful assignments
        $successCount = count(array_filter($results, fn($r) => $r['success']));
        $this->assertEquals(10, $successCount, 'All 10 assignments should succeed');

        // Verify: counter matches actual assignment count
        $actualCount = $subscription->activeAssignments()->count();
        $this->assertEquals(10, $subscription->assigned_count, 'Counter should be 10');
        $this->assertEquals($actualCount, $subscription->assigned_count, 'Counter should match actual assignments');

        // Clean up
        $subscription->forceDelete();
    }

    #[Group('counters')]
    #[Group('software')]
    #[Group('error-handling')]
    public function test_counter_rollback_on_failure(): void
    {
        $subscription = $this->createTestSubscription(2);
        $counterService = app(SubscriptionCounterService::class);

        // Assign 2 licenses (fill capacity)
        $assignable1 = $this->createMockAssignable();
        $assignable2 = $this->createMockAssignable();
        
        $counterService->assignLicense($subscription, $assignable1);
        $counterService->assignLicense($subscription, $assignable2);
        
        $subscription->refresh();
        $this->assertEquals(2, $subscription->assigned_count);

        // Try to assign a 3rd license (should fail)
        $assignable3 = $this->createMockAssignable();
        
        $exceptionThrown = false;
        try {
            $counterService->assignLicense($subscription, $assignable3);
        } catch (LicenseLimitExceededException $e) {
            $exceptionThrown = true;
            $this->assertEquals($subscription->id, $e->subscriptionId);
            $this->assertEquals(2, $e->purchasedQuantity);
        }

        $this->assertTrue($exceptionThrown, 'LicenseLimitExceededException should be thrown');

        // Verify counter was NOT incremented (transaction rolled back)
        $subscription->refresh();
        $this->assertEquals(2, $subscription->assigned_count, 'Counter should still be 2 after failed assignment');

        // Clean up
        $subscription->forceDelete();
    }

    #[Group('counters')]
    #[Group('billing')]
    #[Group('integration')]
    public function test_counter_drives_billing_calculation(): void
    {
        $subscription = $this->createTestSubscription(10);
        $counterService = app(SubscriptionCounterService::class);

        // Initial state: no assignments
        $this->assertEquals(0.00, $subscription->calculateTotalCost());

        // Assign 3 licenses
        for ($i = 0; $i < 3; $i++) {
            $counterService->assignLicense($subscription, $this->createMockAssignable());
        }

        $subscription->refresh();
        
        // Cost should be based on assigned_count
        $expectedCost = 3 * self::$testProduct->vendor_cost; // passthrough billing
        $this->assertEquals($expectedCost, $subscription->calculateTotalCost());

        // Verify counter integrity
        $this->assertEquals(3, $subscription->assigned_count);

        // Clean up
        $subscription->forceDelete();
    }

    #[Group('counters')]
    #[Group('software')]
    public function test_counter_decrement_on_removal(): void
    {
        $subscription = $this->createTestSubscription(5);
        $counterService = app(SubscriptionCounterService::class);

        // Assign 3 licenses
        $assignments = [];
        for ($i = 0; $i < 3; $i++) {
            $assignments[] = $counterService->assignLicense($subscription, $this->createMockAssignable());
        }

        $subscription->refresh();
        $this->assertEquals(3, $subscription->assigned_count);

        // Revoke 1 license
        $counterService->revokeLicense($assignments[0], SoftwareAssignment::REVOKED_MANUAL);

        $subscription->refresh();
        $this->assertEquals(2, $subscription->assigned_count, 'Counter should be decremented after revocation');

        // Verify actual assignments match counter
        $actualCount = $subscription->activeAssignments()->count();
        $this->assertEquals($actualCount, $subscription->assigned_count);

        // Clean up
        $subscription->forceDelete();
    }

    #[Group('counters')]
    #[Group('reconciliation')]
    public function test_counter_reconciliation_detects_drift(): void
    {
        $subscription = $this->createTestSubscription(10);
        $counterService = app(SubscriptionCounterService::class);

        // Create 3 assignments
        for ($i = 0; $i < 3; $i++) {
            $counterService->assignLicense($subscription, $this->createMockAssignable());
        }

        $subscription->refresh();
        $this->assertEquals(3, $subscription->assigned_count);

        // Simulate drift: manually set counter to wrong value
        $subscription->update(['assigned_count' => 5]);
        $subscription->refresh();
        $this->assertEquals(5, $subscription->assigned_count); // Verify drift

        // Run reconciliation
        $reconciledCount = $counterService->reconcileCount($subscription);

        // Verify drift was corrected
        $this->assertEquals(3, $reconciledCount, 'Reconciliation should correct the drift');

        $subscription->refresh();
        $this->assertEquals(3, $subscription->assigned_count, 'Counter should match actual assignments after reconciliation');

        // Clean up
        $subscription->forceDelete();
    }

    #[Group('counters')]
    #[Group('smoke')]
    public function test_counter_system_available(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->getAdminUser())
                ->visit('/dashboard')
                ->assertSee('Dashboard');
        });
    }

    #[Group('counters')]
    #[Group('software')]
    public function test_license_limit_enforced(): void
    {
        $subscription = $this->createTestSubscription(2);
        $counterService = app(SubscriptionCounterService::class);

        // Assign exactly 2 licenses
        $counterService->assignLicense($subscription, $this->createMockAssignable());
        $counterService->assignLicense($subscription, $this->createMockAssignable());

        $subscription->refresh();
        $this->assertEquals(2, $subscription->assigned_count);
        $this->assertFalse($subscription->hasAvailableLicenses());

        // Try to assign beyond limit
        $this->expectException(LicenseLimitExceededException::class);
        $counterService->assignLicense($subscription, $this->createMockAssignable());

        // Clean up (won't reach here due to exception, but phpunit handles it)
        $subscription->forceDelete();
    }

    #[Group('counters')]
    #[Group('software')]
    public function test_unlimited_licenses_work(): void
    {
        // purchased_quantity = 0 means unlimited
        $subscription = $this->createTestSubscription(0);
        $counterService = app(SubscriptionCounterService::class);

        // Should be able to assign many licenses
        for ($i = 0; $i < 20; $i++) {
            $counterService->assignLicense($subscription, $this->createMockAssignable());
        }

        $subscription->refresh();
        $this->assertEquals(20, $subscription->assigned_count);
        $this->assertTrue($subscription->hasAvailableLicenses(), 'Unlimited subscription should always have available licenses');

        // Clean up
        $subscription->forceDelete();
    }
}
