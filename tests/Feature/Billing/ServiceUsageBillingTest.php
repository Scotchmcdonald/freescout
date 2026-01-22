<?php

namespace Tests\Feature\Billing;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ContractManager\Models\BillingTemplate;
use Modules\Crm\Models\Client;
use Modules\PIB\Models\ServiceUsage;
use Modules\PIB\Services\InvoiceGenerator;
use Tests\TestCase;

class ServiceUsageBillingTest extends TestCase
{
    use RefreshDatabase;

    protected Client $client;
    protected InvoiceGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->client = Client::factory()->create();
        $this->generator = app(InvoiceGenerator::class);
    }

    /**
     * Test that unbilled ServiceUsage records are included in the invoice.
     * 
     * Scenario:
     * - Client has 2 hours of unbilled labor ($150/hr).
     * - Billing Template runs (Monthly).
     * - Expected: Invoice includes $300 for Labor.
     */
    public function test_unbilled_service_usage_is_invoiced(): void
    {
        // 1. Setup Template (Standard Monthly)
        $template = BillingTemplate::create([
            'client_id' => $this->client->id,
            'name' => 'Monthly Billing',
            'product_type' => 'service_plan',
            'product_config' => [
                'plan_name' => 'Retainer',
                'base_price' => 500.00, // Fixed fee
            ],
            'billing_cycle' => 'monthly',
            'next_invoice_date' => now(),
            'status' => 'active',
        ]);

        // 2. Create Unbilled Service Usage
        $labor = ServiceUsage::create([
            'client_id' => $this->client->id,
            'service_type' => ServiceUsage::TYPE_LABOR,
            'hours' => 2.0,
            'hourly_rate' => 150.00,
            'description' => 'Emergency Server Fix',
            'service_date' => now()->subDays(2),
            'status' => 'approved', // Ready to bill
            'invoice_id' => null, // Not billed yet
        ]);

        // 3. Generate Invoice
        $invoice = $this->generator->generateFromTemplate($template);

        // 4. Assert
        $this->assertNotNull($invoice);
        
        // Base Price (500) + Labor (300) = 800
        // Currently, InvoiceGenerator likely only does Base Price (500).
        $expectedTotal = 500.00 + 300.00;

        $this->assertEquals(
            $expectedTotal, 
            $invoice->total_amount, 
            "Invoice total should include service usage. Found: {$invoice->total_amount}, Expected: {$expectedTotal}"
        );

        // Verify line item existence
        $hasLaborLine = $invoice->lineItems()
            ->where('description', 'like', '%Emergency Server Fix%')
            ->exists();
            
        $this->assertTrue($hasLaborLine, "Invoice should contain line item for Service Usage");

        // Verify ServiceUsage record was marked as billed
        $labor->refresh();
        $this->assertEquals($invoice->id, $labor->invoice_id, "ServiceUsage record should be linked to invoice");
    }
}
