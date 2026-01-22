<?php

declare(strict_types=1);

namespace Tests\Feature\PIB;

use App\Services\EntitlementEngine;
use Modules\PIB\Events\InvoiceGenerated;
use Modules\PIB\Events\InvoiceUnusual;
use Modules\PIB\Events\RentToOwnGoalReached;
use Modules\PIB\Jobs\GenerateRecurringInvoicesJob;
use Modules\PIB\Models\BillingTemplate;
use Modules\PIB\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * GenerateRecurringInvoicesJobTest
 * 
 * Tests the job that generates recurring invoices with unusual detection
 */
class GenerateRecurringInvoicesJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        Event::fake();
    }

    public function test_generates_invoice_for_due_template(): void
    {
        $client = \App\Models\User::factory()->create();

        // Setup counters for Silver Plan
        $this->setupCounters($client->id, 5, 5, 0);

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

        $job = new GenerateRecurringInvoicesJob();
        $job->handle(app(EntitlementEngine::class));

        // Verify invoice created
        $invoice = Invoice::where('billing_template_id', $template->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(250.00, $invoice->total_amount); // 5 users * $50
        $this->assertEquals('draft', $invoice->status);

        // Verify line items created
        $this->assertCount(1, $invoice->lineItems);

        // Verify event fired
        Event::assertDispatched(InvoiceGenerated::class);

        // Verify next invoice date updated
        $template->refresh();
        $this->assertEquals(today()->addMonth(), $template->next_invoice_date);
    }

    public function test_does_not_generate_for_future_date(): void
    {
        $client = \App\Models\User::factory()->create();
        $this->setupCounters($client->id, 5, 5, 0);

        $template = BillingTemplate::create([
            'client_id' => $client->id,
            'product_type' => 'silver_plan',
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
        $this->assertEquals(0, Invoice::count());
    }

    public function test_does_not_generate_for_paused_template(): void
    {
        $client = \App\Models\User::factory()->create();
        $this->setupCounters($client->id, 5, 5, 0);

        $template = BillingTemplate::create([
            'client_id' => $client->id,
            'product_type' => 'silver_plan',
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
        $this->assertEquals(0, Invoice::count());
    }

    public function test_detects_unusual_amount(): void
    {
        $client = \App\Models\User::factory()->create();
        $this->setupCounters($client->id, 5, 5, 0);

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
    }

    public function test_completes_rent_to_own_template_at_goal(): void
    {
        $client = \App\Models\User::factory()->create();

        $template = BillingTemplate::create([
            'client_id' => $client->id,
            'product_type' => 'rent_to_own',
            'product_config' => [
                'goal_amount' => 250.00, // Small goal for testing
                'monthly_installment' => 250.00,
            ],
            'billing_cycle' => 'monthly',
            'next_invoice_date' => today(),
            'status' => 'active',
        ]);

        // No previous payments, so this should be the final payment
        $job = new GenerateRecurringInvoicesJob();
        $job->handle(app(EntitlementEngine::class));

        // Create the invoice as paid to simulate payment
        $invoice = Invoice::where('billing_template_id', $template->id)->first();
        $invoice->update(['status' => 'paid', 'paid_at' => now()]);

        // Run job again - should detect goal reached
        $template->update(['next_invoice_date' => today()->addMonth()]);
        $job = new GenerateRecurringInvoicesJob();
        $job->handle(app(EntitlementEngine::class));

        // Verify template marked as completed
        $template->refresh();
        $this->assertEquals('completed', $template->status);

        // Verify event fired
        Event::assertDispatched(RentToOwnGoalReached::class);
    }

    public function test_generates_unique_invoice_numbers(): void
    {
        $client = \App\Models\User::factory()->create();
        $this->setupCounters($client->id, 5, 5, 0);

        // Create multiple templates
        $template1 = BillingTemplate::create([
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

        $template2 = BillingTemplate::create([
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

        $job = new GenerateRecurringInvoicesJob();
        $job->handle(app(EntitlementEngine::class));

        // Verify unique invoice numbers
        $invoices = Invoice::all();
        $this->assertCount(2, $invoices);
        $this->assertNotEquals($invoices[0]->invoice_number, $invoices[1]->invoice_number);
    }

    public function test_handles_quarterly_billing_cycle(): void
    {
        $client = \App\Models\User::factory()->create();
        $this->setupCounters($client->id, 5, 5, 0);

        $template = BillingTemplate::create([
            'client_id' => $client->id,
            'product_type' => 'silver_plan',
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
        $this->assertEquals(today()->addMonths(3), $template->next_invoice_date);
    }

    /**
     * Helper to setup counter tables
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
