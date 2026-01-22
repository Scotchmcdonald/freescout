<?php

declare(strict_types=1);

namespace Tests\Feature\PIB;

use App\Services\AtomicCounterService;
use App\Services\EntitlementEngine;
use Modules\PIB\Models\BillingTemplate;
use Modules\PIB\Resolvers\ServicePlanEntitlementResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ServicePlanEntitlementResolverTest
 * 
 * Tests the generic Service Plan resolver with various tier configurations
 * (Silver, Gold, Platinum, Basic)
 */
class ServicePlanEntitlementResolverTest extends TestCase
{
    use RefreshDatabase;

    private AtomicCounterService $counterService;
    private ServicePlanEntitlementResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->counterService = app(AtomicCounterService::class);
        $this->resolver = new ServicePlanEntitlementResolver($this->counterService);
    }

    /**
     * @test
     * Silver tier: 1 asset per user included
     */
    public function test_silver_tier_calculates_with_one_asset_per_user(): void
    {
        $client = \App\Models\User::factory()->create();

        $template = BillingTemplate::create([
            'client_id' => $client->id,
            'product_type' => 'service_plan',
            'product_config' => [
                'plan_tier' => 'silver',
                'plan_display_name' => 'Silver Plan',
                'base_rate_per_user' => 50.00,
                'additional_asset_rate' => 5.00,
                'included_assets_per_user' => 1,
            ],
            'billing_cycle' => 'monthly',
            'next_invoice_date' => today(),
            'status' => 'active',
        ]);

        // 5 users, 5 assets (exactly 1 per user - no additional)
        $this->setupCounters($client->id, 5, 5, 0);

        $result = $this->resolver->calculate($template);

        // 5 users * $50 = $250, no additional assets
        $this->assertEquals(250.00, $result->amount);
        $this->assertEquals(5, $result->quantity);
        $this->assertCount(1, $result->breakdown);
        $this->assertEquals('Silver Plan - Base Users', $result->breakdown[0]['description']);
    }

    /**
     * @test
     * Gold tier: 2 assets per user included
     */
    public function test_gold_tier_calculates_with_two_assets_per_user(): void
    {
        $client = \App\Models\User::factory()->create();

        $template = BillingTemplate::create([
            'client_id' => $client->id,
            'product_type' => 'service_plan',
            'product_config' => [
                'plan_tier' => 'gold',
                'plan_display_name' => 'Gold Plan',
                'base_rate_per_user' => 75.00,
                'additional_asset_rate' => 5.00,
                'included_assets_per_user' => 2,
            ],
            'billing_cycle' => 'monthly',
            'next_invoice_date' => today(),
            'status' => 'active',
        ]);

        // 5 users, 10 assets (exactly 2 per user - no additional)
        $this->setupCounters($client->id, 5, 10, 0);

        $result = $this->resolver->calculate($template);

        // 5 users * $75 = $375, no additional assets
        $this->assertEquals(375.00, $result->amount);
        $this->assertEquals(5, $result->quantity);
        $this->assertCount(1, $result->breakdown);
        $this->assertEquals('Gold Plan - Base Users', $result->breakdown[0]['description']);
    }

    /**
     * @test
     * Gold tier with additional assets beyond allocation
     */
    public function test_gold_tier_charges_for_assets_beyond_allocation(): void
    {
        $client = \App\Models\User::factory()->create();

        $template = BillingTemplate::create([
            'client_id' => $client->id,
            'product_type' => 'service_plan',
            'product_config' => [
                'plan_tier' => 'gold',
                'plan_display_name' => 'Gold Plan',
                'base_rate_per_user' => 75.00,
                'additional_asset_rate' => 5.00,
                'included_assets_per_user' => 2,
            ],
            'billing_cycle' => 'monthly',
            'next_invoice_date' => today(),
            'status' => 'active',
        ]);

        // 5 users, 15 assets (5 additional beyond 2 per user)
        $this->setupCounters($client->id, 5, 15, 0);

        $result = $this->resolver->calculate($template);

        // (5 * $75) + (5 * $5) = $375 + $25 = $400
        $this->assertEquals(400.00, $result->amount);
        $this->assertCount(2, $result->breakdown);
        $this->assertEquals('Additional Assets', $result->breakdown[1]['description']);
        $this->assertEquals(5, $result->breakdown[1]['quantity']);
    }

    /**
     * @test
     * Platinum tier: unlimited assets per user included
     */
    public function test_platinum_tier_unlimited_user_assets(): void
    {
        $client = \App\Models\User::factory()->create();

        $template = BillingTemplate::create([
            'client_id' => $client->id,
            'product_type' => 'service_plan',
            'product_config' => [
                'plan_tier' => 'platinum',
                'plan_display_name' => 'Platinum Plan',
                'base_rate_per_user' => 150.00,
                'additional_asset_rate' => 5.00,
                'included_assets_per_user' => -1, // Unlimited
            ],
            'billing_cycle' => 'monthly',
            'next_invoice_date' => today(),
            'status' => 'active',
        ]);

        // 5 users, 100 user-assigned assets (all included), 3 non-allocated
        $this->setupCounters($client->id, 5, 100, 3);

        $result = $this->resolver->calculate($template);

        // (5 * $150) + (3 * $5) = $750 + $15 = $765
        // Only non-allocated assets are charged
        $this->assertEquals(765.00, $result->amount);
        $this->assertCount(2, $result->breakdown);
        $this->assertEquals('Platinum Plan - Base Users', $result->breakdown[0]['description']);
        $this->assertEquals('Additional Assets', $result->breakdown[1]['description']);
        $this->assertEquals(3, $result->breakdown[1]['quantity']); // Only non-allocated
    }

    /**
     * @test
     * Non-allocated assets are always charged regardless of tier
     */
    public function test_non_allocated_assets_always_charged(): void
    {
        $client = \App\Models\User::factory()->create();

        $template = BillingTemplate::create([
            'client_id' => $client->id,
            'product_type' => 'service_plan',
            'product_config' => [
                'plan_tier' => 'silver',
                'plan_display_name' => 'Silver Plan',
                'base_rate_per_user' => 50.00,
                'additional_asset_rate' => 5.00,
                'included_assets_per_user' => 1,
            ],
            'billing_cycle' => 'monthly',
            'next_invoice_date' => today(),
            'status' => 'active',
        ]);

        // 5 users, 5 user-assigned (within allocation), 10 non-allocated
        $this->setupCounters($client->id, 5, 5, 10);

        $result = $this->resolver->calculate($template);

        // (5 * $50) + (10 * $5) = $250 + $50 = $300
        $this->assertEquals(300.00, $result->amount);
        $this->assertEquals(10, $result->breakdown[1]['quantity']);
    }

    /**
     * @test
     */
    public function test_throws_exception_for_missing_config(): void
    {
        $client = \App\Models\User::factory()->create();

        $template = BillingTemplate::create([
            'client_id' => $client->id,
            'product_type' => 'service_plan',
            'product_config' => [
                'plan_tier' => 'silver',
                // Missing base_rate_per_user and additional_asset_rate
            ],
            'billing_cycle' => 'monthly',
            'next_invoice_date' => today(),
            'status' => 'active',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Service Plan config missing required key');

        $this->resolver->calculate($template);
    }

    /**
     * @test
     * Plan display name defaults to tier name if not provided
     */
    public function test_defaults_display_name_from_tier(): void
    {
        $client = \App\Models\User::factory()->create();

        $template = BillingTemplate::create([
            'client_id' => $client->id,
            'product_type' => 'service_plan',
            'product_config' => [
                'plan_tier' => 'basic',
                // No plan_display_name - should default to "Basic Plan"
                'base_rate_per_user' => 25.00,
                'additional_asset_rate' => 5.00,
                'included_assets_per_user' => 1,
            ],
            'billing_cycle' => 'monthly',
            'next_invoice_date' => today(),
            'status' => 'active',
        ]);

        $this->setupCounters($client->id, 3, 3, 0);

        $result = $this->resolver->calculate($template);

        $this->assertEquals('Basic Plan - Base Users', $result->breakdown[0]['description']);
    }

    /**
     * Helper: Setup counter values for a client
     */
    private function setupCounters(int $clientId, int $userCount, int $userAssets, int $nonAllocatedAssets): void
    {
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
