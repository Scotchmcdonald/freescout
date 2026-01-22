<?php

namespace Tests\Feature\Billing;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\AssetManagement\Entities\Asset;
use Modules\ContractManager\Models\BillingTemplate;
use Modules\Crm\Models\Client;
use Modules\PIB\Models\ServiceUsage;
use Modules\PIB\Services\InvoiceGenerator;
use Tests\TestCase;

class BillingEdgeCasesTest extends TestCase
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
     * Edge Case: Asset added on the very first day of the cycle should be billed 100%.
     */
    public function test_proration_first_day_full_charge(): void
    {
        $template = $this->createProratedTemplate();
        
        // Asset created exactly at start of period
        $this->createBackdatedAsset('Asset-Day-1', '2024-01-01');

        $invoice = $this->runBilling('2024-02-01', '2024-01-01', '2024-01-31', $template);

        // Should be exactly $100
        $this->assertEquals(100.00, $invoice->total_amount, "First day asset should be billed 100%");
    }

    /**
     * Edge Case: Asset added on the very last day of the cycle should be billed ~1/31th.
     */
    public function test_proration_last_day_charge(): void
    {
        $template = $this->createProratedTemplate();
        
        // Asset created on Jan 31st
        $this->createBackdatedAsset('Asset-Day-31', '2024-01-31');

        $invoice = $this->runBilling('2024-02-01', '2024-01-01', '2024-01-31', $template);

        // 1 day out of 31 days * $100 = 3.2258...
        $expected = (1 / 31) * 100;
        
        $this->assertEqualsWithDelta($expected, $invoice->total_amount, 0.02, "Last day asset should be billed ~1 day");
    }

    /**
     * Edge Case: Multiple service usage items of different types
     */
    public function test_multiple_service_usage_items_aggregation(): void
    {
        $template = $this->createStandardTemplate();
        
        // 1. Labor: 2 hours @ $100 = $200
        ServiceUsage::create([
            'client_id' => $this->client->id,
            'service_type' => ServiceUsage::TYPE_LABOR,
            'hours' => 2.0,
            'hourly_rate' => 100.00,
            'description' => 'Fixing Printer',
            'service_date' => now(),
            'status' => 'approved',
        ]);

        // 2. Consultation: 1 hour @ $200 = $200
        ServiceUsage::create([
            'client_id' => $this->client->id,
            'service_type' => ServiceUsage::TYPE_CONSULTATION,
            'hours' => 1.0,
            'hourly_rate' => 200.00, // Higher rate
            'description' => 'Security Audit',
            'service_date' => now(),
            'status' => 'approved',
        ]);

        $invoice = $this->generator->generateFromTemplate($template);

        // Base 500 + 200 + 200 = 900
        $this->assertEquals(900.00, $invoice->total_amount);
        $this->assertCount(3, $invoice->lineItems, "Should have 3 line items (Base + Labor + Consult)");
    }

    /**
     * Edge Case: Idempotency. Running billing twice shouldn't double-charge service usage.
     */
    public function test_service_usage_is_billed_once_only(): void
    {
        $template = $this->createStandardTemplate();
        
        // Create 1 Billable Item ($100)
        ServiceUsage::create([
            'client_id' => $this->client->id,
            'service_type' => ServiceUsage::TYPE_LABOR,
            'hours' => 1.0,
            'hourly_rate' => 100.00,
            'status' => 'approved',
            'service_date' => now(), // Corrected: Added required field
        ]);

        // Run 1
        $invoice1 = $this->generator->generateFromTemplate($template);
        $this->assertEquals(600.00, $invoice1->total_amount); // 500 Base + 100 Usage

        // Run 2 (Same period)
        $invoice2 = $this->generator->generateFromTemplate($template);
        
        // usage item is now attached to invoice1, so it should NOT be in invoice2
        $this->assertEquals(500.00, $invoice2->total_amount, "Second run should only contain base charge");
    }

    // --- Helpers ---

    private function createProratedTemplate(): BillingTemplate
    {
        return BillingTemplate::create([
            'client_id' => $this->client->id,
            'name' => 'Proration Template',
            'product_type' => 'service_plan',
            'product_config' => [
                'plan_name' => 'Managed Assets',
                'base_price' => 0,
                'per_asset_price' => 100.00,
            ],
            'billing_cycle' => 'monthly',
            'next_invoice_date' => Carbon::parse('2024-02-01'),
            'proration_enabled' => true,
            'status' => 'active',
            'activated_at' => Carbon::parse('2024-01-01'),
        ]);
    }

    private function createStandardTemplate(): BillingTemplate
    {
        return BillingTemplate::create([
            'client_id' => $this->client->id,
            'name' => 'Standard Template',
            'product_type' => 'service_plan',
            'product_config' => [
                'plan_name' => 'Base Retainer',
                'base_price' => 500.00,
            ],
            'billing_cycle' => 'monthly',
            'next_invoice_date' => now(),
            'status' => 'active',
        ]);
    }

    private function createBackdatedAsset(string $serial, string $date): void
    {
        $asset = Asset::create([
            'client_id' => $this->client->id,
            'asset_type' => 'workstation',
            'status' => 'active',
            'serial_number' => $serial,
            'source' => 'manual',
        ]);
        
        DB::table($asset->getTable())->where('id', $asset->id)->update([
            'created_at' => Carbon::parse($date),
            'updated_at' => Carbon::parse($date),
        ]);
    }

    /**
     * Edge Case: Invoice numbers must be unique across different clients generated at the same time.
     * Found Bug: Previously failed with UniqueConstraintViolation.
     */
    public function test_invoice_numbers_are_globally_unique(): void
    {
        $client2 = Client::factory()->create();
        
        $template1 = $this->createStandardTemplate(); // For $this->client
        
        $template2 = BillingTemplate::create([
            'client_id' => $client2->id,
            'name' => 'Template 2',
            'product_type' => 'service_plan',
            'product_config' => ['base_price' => 50],
            'billing_cycle' => 'monthly',
            'status' => 'active',
        ]);

        // Freeze time to force collision if logic is flawed
        Carbon::setTestNow('2025-01-01 12:00:00');
        
        $invoice1 = $this->generator->generateFromTemplate($template1);
        $invoice2 = $this->generator->generateFromTemplate($template2);
        
        $this->assertNotEquals($invoice1->invoice_number, $invoice2->invoice_number);
        
        // Verify sequence
        // Expected format: INV-20250101-0001, INV-20250101-0002
        $this->assertStringContainsString('0001', $invoice1->invoice_number);
        $this->assertStringContainsString('0002', $invoice2->invoice_number);
    }

    private function runBilling($invoiceDate, $start, $end, $template)
    {
        return $this->generator->generateFromTemplate(
            $template,
            Carbon::parse($invoiceDate),
            Carbon::parse($start),
            Carbon::parse($end)->endOfDay()
        );
    }
}
