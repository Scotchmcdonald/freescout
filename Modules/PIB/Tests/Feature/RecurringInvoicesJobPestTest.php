<?php

use Modules\ContractManager\Models\BillingTemplate;
use Modules\PIB\Models\Invoice;
use Modules\PIB\Jobs\GenerateRecurringInvoicesJob;
use Modules\PIB\Events\InvoiceGenerated;
use Modules\PIB\Events\InvoiceUnusual;
use Modules\PIB\Events\RentToOwnGoalReached;
use Modules\PIB\Services\EntitlementEngineService as EntitlementEngine;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Modules\Crm\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Helper function
function setupCountersJob(int $clientId, int $userCount, int $userAssets, int $nonAllocatedAssets) {
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

describe('GenerateRecurringInvoicesJob', function () {
    beforeEach(function () {
        Event::fake([
            \Modules\PIB\Events\InvoiceGenerated::class,
            \Modules\PIB\Events\InvoiceUnusual::class,
            \Modules\PIB\Events\RentToOwnGoalReached::class,
        ]);
    });

    test('generates invoice for due template', function () {
        $client = Client::factory()->create();

        // Setup counters for Silver Plan
        setupCountersJob($client->id, 5, 5, 0);

        $template = BillingTemplate::create([
            'client_id' => $client->id,
            'name' => 'Test Template',
            'product_type' => 'subscription_service_plan',
            'product_config' => [
                'base_rate_per_user' => 50.00,
                'additional_asset_rate' => 5.00,
            ],
            'billing_cycle' => 'monthly',
            'next_invoice_date' => today(),
            'status' => 'active',
        ]);

        $job = new GenerateRecurringInvoicesJob();
        $job->handle(app(EntitlementEngine::class));

        // Verify invoice created
        $invoice = Invoice::where('billing_template_id', $template->id)->first();
        expect($invoice)->not->toBeNull()
            ->and((float) $invoice->total_amount)->toBe(250.00) // 5 users * $50
            ->and($invoice->status)->toBe('draft');

        // Verify line items created
        expect($invoice->lineItems)->toHaveCount(1);

        // Verify event fired
        Event::assertDispatched(InvoiceGenerated::class);

        // Verify next invoice date updated
        $template->refresh();
        expect($template->next_invoice_date->toDateString())->toBe(today()->addMonth()->toDateString());
    });

    test('does not generate for future date', function () {
        $client = Client::factory()->create();
        setupCountersJob($client->id, 5, 5, 0);

        $template = BillingTemplate::create([
            'client_id' => $client->id,
            'name' => 'Test Template',
            'product_type' => 'subscription_service_plan',
            'product_config' => [
                'base_rate_per_user' => 50.00,
                'additional_asset_rate' => 5.00,
            ],
            'billing_cycle' => 'monthly',
            'next_invoice_date' => today()->addWeek(),
            'status' => 'active',
        ]);

        $job = new GenerateRecurringInvoicesJob();
        $job->handle(app(EntitlementEngine::class));

        // No invoice should be created
        expect(Invoice::count())->toBe(0);
    });

    test('does not generate for paused template', function () {
        $client = Client::factory()->create();
        setupCountersJob($client->id, 5, 5, 0);

        $template = BillingTemplate::create([
            'client_id' => $client->id,
            'name' => 'Test Template',
            'product_type' => 'subscription_service_plan',
            'product_config' => [
                'base_rate_per_user' => 50.00,
                'additional_asset_rate' => 5.00,
            ],
            'billing_cycle' => 'monthly',
            'next_invoice_date' => today(),
            'status' => 'paused',
        ]);

        $job = new GenerateRecurringInvoicesJob();
        $job->handle(app(EntitlementEngine::class));

        // No invoice should be created
        expect(Invoice::count())->toBe(0);
    });

    test('detects unusual amount', function () {
        $client = Client::factory()->create();
        setupCountersJob($client->id, 5, 5, 0);

        $template = BillingTemplate::create([
            'client_id' => $client->id,
            'name' => 'Test Template',
            'product_type' => 'subscription_service_plan',
            'product_config' => [
                'base_rate_per_user' => 50.00,
                'additional_asset_rate' => 5.00,
            ],
            'billing_cycle' => 'monthly',
            'next_invoice_date' => today(),
            'status' => 'active',
        ]);

        // Create a previous invoice with much lower amount
        Invoice::create([
            'client_id' => $client->id,
            'billing_template_id' => $template->id,
            'invoice_number' => 'INV-PREV-001',
            'status' => 'paid',
            'invoice_date' => today()->subMonth(),
            'due_date' => today()->subMonth()->addDays(30),
            'subtotal' => 100.00,
            'tax_amount' => 0.00,
            'total_amount' => 100.00,
            'paid_at' => today()->subMonth(),
        ]);

        // Current calculation: $250 (5 users * $50)
        // Previous: $100
        // Change: 150% (>20% threshold)
        
        $job = new GenerateRecurringInvoicesJob();
        $job->handle(app(EntitlementEngine::class));

        // Verify unusual event fired
        Event::assertDispatched(InvoiceUnusual::class, function ($event) {
            return $event->currentAmount === 250.00 
                && $event->previousAmount === 100.00 
                && abs($event->percentageChange) > 20.0;
        });
    });

    test('completes rent to own template at goal', function () {
        $client = Client::factory()->create();

        $template = BillingTemplate::create([
            'client_id' => $client->id,
            'name' => 'Test Template',
            'product_type' => 'rent_to_own',
            'product_config' => [
                'goal_amount' => 250.00,
                'monthly_installment' => 250.00,
            ],
            'billing_cycle' => 'monthly',
            'next_invoice_date' => today(),
            'status' => 'active',
        ]);

        // Pre-populate a paid invoice so totalPaid already equals goal
        Invoice::create([
            'client_id' => $client->id,
            'billing_template_id' => $template->id,
            'invoice_number' => 'INV-PREPAID-001',
            'status' => 'paid',
            'invoice_date' => today()->subMonth(),
            'due_date' => today()->subMonth()->addDays(30),
            'subtotal' => 250.00,
            'tax_amount' => 0.00,
            'total_amount' => 250.00,
            'paid_at' => now()->subMonth(),
        ]);

        // Run job - resolver should detect goal reached (totalPaid=250 >= goal=250)
        $job = new GenerateRecurringInvoicesJob();
        $job->handle(app(EntitlementEngine::class));

        // Verify template marked as completed
        $template->refresh();
        expect($template->status)->toBe('completed');

        // Verify event fired
        Event::assertDispatched(RentToOwnGoalReached::class);
    });

    test('generates unique invoice numbers', function () {
        $client = Client::factory()->create();
        setupCountersJob($client->id, 5, 5, 0);

        // Create multiple templates
        $template1 = BillingTemplate::create([
            'client_id' => $client->id,
            'name' => 'Test Template',
            'product_type' => 'subscription_service_plan',
            'product_config' => [
                'base_rate_per_user' => 50.00,
                'additional_asset_rate' => 5.00,
            ],
            'billing_cycle' => 'monthly',
            'next_invoice_date' => today(),
            'status' => 'active',
        ]);

        $template2 = BillingTemplate::create([
            'client_id' => $client->id,
            'name' => 'Test Template',
            'product_type' => 'subscription_service_plan',
            'product_config' => [
                'base_rate_per_user' => 50.00,
                'additional_asset_rate' => 5.00,
            ],
            'billing_cycle' => 'monthly',
            'next_invoice_date' => today(),
            'status' => 'active',
        ]);

        $job = new GenerateRecurringInvoicesJob();
        $job->handle(app(EntitlementEngine::class));

        // Verify unique invoice numbers
        $invoices = Invoice::all();
        expect($invoices)->toHaveCount(2)
            ->and($invoices[0]->invoice_number)->not->toEqual($invoices[1]->invoice_number);
    });

    test('handles quarterly billing cycle', function () {
        $client = Client::factory()->create();
        setupCountersJob($client->id, 5, 5, 0);

        $template = BillingTemplate::create([
            'client_id' => $client->id,
            'name' => 'Test Template',
            'product_type' => 'subscription_service_plan',
            'product_config' => [
                'base_rate_per_user' => 50.00,
                'additional_asset_rate' => 5.00,
            ],
            'billing_cycle' => 'quarterly',
            'next_invoice_date' => today(),
            'status' => 'active',
        ]);

        $job = new GenerateRecurringInvoicesJob();
        $job->handle(app(EntitlementEngine::class));

        // Verify next invoice date is 3 months later
        $template->refresh();
        expect($template->next_invoice_date->toDateString())->toBe(today()->addMonths(3)->toDateString());
    });
});
