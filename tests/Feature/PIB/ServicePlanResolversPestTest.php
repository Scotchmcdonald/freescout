<?php

use App\Services\AtomicCounterService;
use Modules\PIB\Models\BillingTemplate;
use Modules\PIB\Resolvers\ServicePlanEntitlementResolver;
use Modules\PIB\Resolvers\SilverPlanEntitlementResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Modules\Crm\Models\Client;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// Helper function
function setupCounters(int $clientId, int $userCount, int $userAssets, int $nonAllocatedAssets) {
    // Ensure a Client record exists so the FK on client_user_counters is satisfied
    if (! DB::table('clients')->where('id', $clientId)->exists()) {
        Client::factory()->create(['id' => $clientId]);
    }

    DB::table('client_user_counters')->insert([
        'client_id' => $clientId,
        'active_user_count' => $userCount,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('client_asset_counters')->insert([
        [
            'client_id' => $clientId,
            'asset_type' => 'chromebook',
            'allocation_type' => 'user_assigned',
            'count' => $userAssets,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'client_id' => $clientId,
            'asset_type' => 'chromebook',
            'allocation_type' => 'non_allocated',
            'count' => $nonAllocatedAssets,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);
}

describe('ServicePlanEntitlementResolver', function () {
    beforeEach(function () {
        $this->counterService = app(AtomicCounterService::class);
        $this->resolver = new ServicePlanEntitlementResolver($this->counterService);
    });

    test('silver tier calculates with one asset per user', function () {
        $client = Client::factory()->create();

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

        setupCounters($client->id, 5, 5, 0);

        $result = $this->resolver->calculate($template);

        expect($result->amount)->toBe(250.00)
            ->and($result->quantity)->toBe(5)
            ->and($result->breakdown[0]['description'])->toBe('Silver Plan - Base Users');
    });

    test('gold tier calculates with two assets per user', function () {
        $client = Client::factory()->create();

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

        setupCounters($client->id, 5, 10, 0);

        $result = $this->resolver->calculate($template);

        expect($result->amount)->toBe(375.00)
            ->and($result->quantity)->toBe(5)
            ->and($result->breakdown[0]['description'])->toBe('Gold Plan - Base Users');
    });

    test('gold tier charges for assets beyond allocation', function () {
        $client = Client::factory()->create();

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

        setupCounters($client->id, 5, 15, 0);

        $result = $this->resolver->calculate($template);

        // (5 * 75) + (5 * 5) = 375 + 25 = 400
        expect($result->amount)->toBe(400.00)
            ->and($result->breakdown[1]['description'])->toBe('Additional Assets')
            ->and($result->breakdown[1]['quantity'])->toBe(5);
    });

    test('platinum tier unlimited user assets', function () {
        $client = Client::factory()->create();

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

        setupCounters($client->id, 5, 100, 3);

        $result = $this->resolver->calculate($template);

        // (5 * 150) + (3 * 5) = 750 + 15 = 765
        expect($result->amount)->toBe(765.00)
            ->and($result->breakdown[0]['description'])->toBe('Platinum Plan - Base Users')
            ->and($result->breakdown[1]['description'])->toBe('Additional Assets')
            ->and($result->breakdown[1]['quantity'])->toBe(3);
    });

    test('non allocated assets always charged', function () {
        $client = Client::factory()->create();

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

        setupCounters($client->id, 5, 5, 10);

        $result = $this->resolver->calculate($template);

        // (5 * 50) + (10 * 5) = 250 + 50 = 300
        expect($result->amount)->toBe(300.00)
            ->and($result->breakdown[1]['quantity'])->toBe(10);
    });

    test('throws exception for missing config', function () {
        $client = Client::factory()->create();

        $template = BillingTemplate::create([
            'client_id' => $client->id,
            'product_type' => 'service_plan',
            'product_config' => [
                'plan_tier' => 'silver',
            ],
            'billing_cycle' => 'monthly',
            'next_invoice_date' => today(),
            'status' => 'active',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Service Plan config missing required key');

        $this->resolver->calculate($template);
    });

    test('defaults display name from tier', function () {
        $client = Client::factory()->create();

        $template = BillingTemplate::create([
            'client_id' => $client->id,
            'product_type' => 'service_plan',
            'product_config' => [
                'plan_tier' => 'basic',
                'base_rate_per_user' => 25.00,
                'additional_asset_rate' => 5.00,
                'included_assets_per_user' => 1,
            ],
            'billing_cycle' => 'monthly',
            'next_invoice_date' => today(),
            'status' => 'active',
        ]);

        setupCounters($client->id, 3, 3, 0);

        $result = $this->resolver->calculate($template);

        expect($result->breakdown[0]['description'])->toBe('Basic Plan - Base Users');
    });
});

describe('SilverPlanEntitlementResolver', function () {
    beforeEach(function () {
        $this->counterService = app(AtomicCounterService::class);
        $this->resolver = new SilverPlanEntitlementResolver($this->counterService);
    });

    test('calculates base charge with no additional assets', function () {
        $client = Client::factory()->create();

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

        setupCounters($client->id, 5, 5, 0);

        $result = $this->resolver->calculate($template);

        expect($result->amount)->toBe(250.00)
            ->and($result->quantity)->toBe(5)
            ->and($result->breakdown[0]['description'])->toBe('Silver Plan - Base Users');
    });

    test('calculates with additional user assigned assets', function () {
        $client = Client::factory()->create();

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

        setupCounters($client->id, 5, 8, 0);

        $result = $this->resolver->calculate($template);

        // (5 * 50) + (3 * 5) = 250 + 15 = 265
        expect($result->amount)->toBe(265.00)
            ->and($result->breakdown[1]['description'])->toBe('Additional Assets')
            ->and($result->breakdown[1]['quantity'])->toBe(3);
    });

    test('calculates with non allocated assets', function () {
        $client = Client::factory()->create();

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

        setupCounters($client->id, 5, 5, 3);

        $result = $this->resolver->calculate($template);

        // (5 * 50) + (3 * 5) = 265
        expect($result->amount)->toBe(265.00)
            ->and($result->breakdown[1]['quantity'])->toBe(3);
    });

    test('calculates with both additional and non allocated assets', function () {
        $client = Client::factory()->create();

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

        setupCounters($client->id, 10, 15, 5);

        $result = $this->resolver->calculate($template);

        // (10 * 50) + (10 * 5) = 550
        expect($result->amount)->toBe(550.00)
            ->and($result->breakdown[1]['quantity'])->toBe(10);
    });

    test('handles zero users', function () {
        $client = Client::factory()->create();

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

        setupCounters($client->id, 0, 0, 3);

        $result = $this->resolver->calculate($template);

        // (0 * 50) + (3 * 5) = 15
        expect($result->amount)->toBe(15.00)
            ->and($result->quantity)->toBe(0);
    });

    test('throws exception for missing config', function () {
        $client = Client::factory()->create();

        $template = BillingTemplate::create([
            'client_id' => $client->id,
            'product_type' => 'silver_plan',
            'product_config' => [], 
            'billing_cycle' => 'monthly',
            'next_invoice_date' => today(),
            'status' => 'active',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->resolver->calculate($template);
    });
});
