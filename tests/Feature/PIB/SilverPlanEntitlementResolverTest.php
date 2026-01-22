<?php

declare(strict_types=1);

namespace Tests\Feature\PIB;

use App\Services\AtomicCounterService;
use App\Services\EntitlementEngine;
use Modules\PIB\Models\BillingTemplate;
use Modules\PIB\Resolvers\SilverPlanEntitlementResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SilverPlanEntitlementResolverTest
 * 
 * Tests Silver Plan billing calculations with varying user and asset counts
 */
class SilverPlanEntitlementResolverTest extends TestCase
{
    use RefreshDatabase;

    private AtomicCounterService $counterService;
    private SilverPlanEntitlementResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->counterService = app(AtomicCounterService::class);
        $this->resolver = new SilverPlanEntitlementResolver($this->counterService);
    }

    public function test_calculates_base_charge_with_no_additional_assets(): void
    {
        // Create user (client)
        $client = \App\Models\User::factory()->create();

        // Create billing template
        $template = BillingTemplate::create([
            'client_id' => $client->id,
            'product_type' => 'silver_plan',
            'product_config' => [
                'base_rate_per_user' => 50.00,
                'additional_asset_rate' => 5.00,
            ],
            'billing_cycle' => 'monthly',
            'next_invoice_date' => today(),
            'status' => 'active',
        ]);

        // Setup counters: 5 users, 5 user-assigned assets (exactly 1 per user)
        $this->setupCounters($client->id, 5, 5, 0);

        // Calculate
        $result = $this->resolver->calculate($template);

        // Expected: 5 users * $50 = $250, no additional assets
        $this->assertEquals(250.00, $result->amount);
        $this->assertEquals(5, $result->quantity);
        $this->assertCount(1, $result->breakdown); // Only base charge line
        $this->assertEquals('Silver Plan - Base Users', $result->breakdown[0]['description']);
        $this->assertFalse($result->hasReachedGoal);
    }

    public function test_calculates_with_additional_user_assigned_assets(): void
    {
        $client = \App\Models\User::factory()->create();

        $template = BillingTemplate::create([
            'client_id' => $client->id,
            'product_type' => 'silver_plan',
            'product_config' => [
                'base_rate_per_user' => 50.00,
                'additional_asset_rate' => 5.00,
            ],
            'billing_cycle' => 'monthly',
            'next_invoice_date' => today(),
            'status' => 'active',
        ]);

        // Setup counters: 5 users, 8 user-assigned assets, 0 non-allocated
        // Included: 5 assets (1 per user)
        // Additional: 3 assets
        $this->setupCounters($client->id, 5, 8, 0);

        $result = $this->resolver->calculate($template);

        // Expected: (5 * $50) + (3 * $5) = $250 + $15 = $265
        $this->assertEquals(265.00, $result->amount);
        $this->assertCount(2, $result->breakdown);
        $this->assertEquals('Additional Assets', $result->breakdown[1]['description']);
        $this->assertEquals(3, $result->breakdown[1]['quantity']);
    }

    public function test_calculates_with_non_allocated_assets(): void
    {
        $client = \App\Models\User::factory()->create();

        $template = BillingTemplate::create([
            'client_id' => $client->id,
            'product_type' => 'silver_plan',
            'product_config' => [
                'base_rate_per_user' => 50.00,
                'additional_asset_rate' => 5.00,
            ],
            'billing_cycle' => 'monthly',
            'next_invoice_date' => today(),
            'status' => 'active',
        ]);

        // Setup counters: 5 users, 5 user-assigned, 3 non-allocated
        // All non-allocated assets are charged
        $this->setupCounters($client->id, 5, 5, 3);

        $result = $this->resolver->calculate($template);

        // Expected: (5 * $50) + (3 * $5) = $250 + $15 = $265
        $this->assertEquals(265.00, $result->amount);
        $this->assertEquals(3, $result->breakdown[1]['quantity']);
    }

    public function test_calculates_with_both_additional_and_non_allocated_assets(): void
    {
        $client = \App\Models\User::factory()->create();

        $template = BillingTemplate::create([
            'client_id' => $client->id,
            'product_type' => 'silver_plan',
            'product_config' => [
                'base_rate_per_user' => 50.00,
                'additional_asset_rate' => 5.00,
            ],
            'billing_cycle' => 'monthly',
            'next_invoice_date' => today(),
            'status' => 'active',
        ]);

        // Setup counters: 10 users, 15 user-assigned, 5 non-allocated
        // Included: 10 assets
        // Additional user-assigned: 5 assets
        // Non-allocated: 5 assets
        // Total additional: 10 assets
        $this->setupCounters($client->id, 10, 15, 5);

        $result = $this->resolver->calculate($template);

        // Expected: (10 * $50) + (10 * $5) = $500 + $50 = $550
        $this->assertEquals(550.00, $result->amount);
        $this->assertEquals(10, $result->quantity);
        $this->assertEquals(10, $result->breakdown[1]['quantity']);
    }

    public function test_handles_zero_users(): void
    {
        $client = \App\Models\User::factory()->create();

        $template = BillingTemplate::create([
            'client_id' => $client->id,
            'product_type' => 'silver_plan',
            'product_config' => [
                'base_rate_per_user' => 50.00,
                'additional_asset_rate' => 5.00,
            ],
            'billing_cycle' => 'monthly',
            'next_invoice_date' => today(),
            'status' => 'active',
        ]);

        // No users, but 3 non-allocated assets
        $this->setupCounters($client->id, 0, 0, 3);

        $result = $this->resolver->calculate($template);

        // Expected: (0 * $50) + (3 * $5) = $0 + $15 = $15
        $this->assertEquals(15.00, $result->amount);
        $this->assertEquals(0, $result->quantity);
    }

    public function test_throws_exception_for_missing_config(): void
    {
        $client = \App\Models\User::factory()->create();

        $template = BillingTemplate::create([
            'client_id' => $client->id,
            'product_type' => 'silver_plan',
            'product_config' => [], // Missing required config
            'billing_cycle' => 'monthly',
            'next_invoice_date' => today(),
            'status' => 'active',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->resolver->calculate($template);
    }

    /**
     * Helper to setup counter tables
     */
    private function setupCounters(int $clientId, int $userCount, int $userAssets, int $nonAllocatedAssets): void
    {
        // Create counter records
        \DB::table('client_user_counters')->insert([
            'client_id' => $clientId,
            'active_user_count' => $userCount,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('client_asset_counters')->insert([
            [
                'client_id' => $clientId,
                'allocation_type' => 'user_assigned',
                'count' => $userAssets,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'client_id' => $clientId,
                'allocation_type' => 'non_allocated',
                'count' => $nonAllocatedAssets,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
