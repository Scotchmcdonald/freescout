<?php

namespace Tests\Feature\Billing;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\AssetManagement\Entities\Asset;
use Modules\ContractManager\Models\BillingTemplate;
use Modules\Crm\Models\Client;
use Modules\PIB\Services\InvoiceGenerator;
use Tests\TestCase;

class ProrationIntegrationTest extends TestCase
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
     * Test that adding an asset mid-month results in a prorated charge
     * for the remainder of the month.
     * 
     * Scenario:
     * - Billing Cycle: Monthly (1st to 31st)
     * - Base Price: $0
     * - Per Asset Price: $100
     * - Jan 1: 0 Assets
     * - Jan 15: Add 1 Asset (Active)
     * - Feb 1: Run Invoice for Jan
     * 
     * Expected:
     * - Invoice Total: ~$54.84 (17 days @ $100/mo) 
     *   OR depending on logic, it might be billed in arrears or advance.
     *   If billed in arrears on Feb 1 for Jan period:
     *   Asset was active Jan 15-31 (17 days).
     */
    public function test_mid_month_asset_addition_is_prorated(): void
    {
        // 1. Setup Template
        $template = BillingTemplate::create([
            'client_id' => $this->client->id,
            'name' => 'Asset Proration Test',
            'product_type' => 'service_plan',
            'product_config' => [
                'plan_name' => 'Managed Assets',
                'base_price' => 0,
                'per_asset_price' => 100.00,
            ],
            'billing_cycle' => 'monthly',
            'next_invoice_date' => Carbon::parse('2024-02-01'), // Invoice for January
            'proration_enabled' => true,
            'status' => 'active',
            'activated_at' => Carbon::parse('2024-01-01'),
        ]);

        // 2. Add Asset on Jan 15th
        // Note: We need to manipulate created_at/activated_at if the logic uses it.
        // Since InvoiceGenerator currently uses 'count()', it likely fails this test 
        // by returning $100 (full month) or $0 (if it checks historical count which it doesn't).
        
        // We simulate "Time Travel" by creating an asset and backdating it
        $asset = Asset::create([
            'client_id' => $this->client->id,
            'name' => 'Mid-Month Laptop',
            'asset_type' => 'workstation',
            'status' => 'active',
            'serial_number' => 'ABC-123',
            'source' => 'manual',
        ]);
        
        // Force timestamp update at database level (bypass Model handling)
        DB::table($asset->getTable())->where('id', $asset->id)->update([
            'created_at' => Carbon::parse('2024-01-15'),
            'updated_at' => Carbon::parse('2024-01-15'),
        ]);
        
        $asset->refresh();

        // 3. Generate Invoice for Period: Jan 1 - Jan 31
        // Executed on Feb 1
        $invoiceDate = Carbon::parse('2024-02-01');
        $periodStart = Carbon::parse('2024-01-01');
        $periodEnd = Carbon::parse('2024-01-31');

        $invoice = $this->generator->generateFromTemplate(
            $template, 
            $invoiceDate, 
            $periodStart, 
            $periodEnd
        );

        // 4. Assert
        // Full month would be 100. Prorated (approx half) should be ~54.84
        // If system merely counts "Active Assets Now", it will see 1 asset and charge $100.
        
        $this->assertNotNull($invoice);
        
        // We expect Proration to be APPLIED (fail if it charges full price)
        $this->assertLessThan(
            100.00, 
            $invoice->total_amount, 
            "Invoice should be prorated for mid-month asset addition. Charged full amount: {$invoice->total_amount}"
        );
        
        $this->assertGreaterThan(
            50.00, 
            $invoice->total_amount, 
            "Invoice too low, ensure asset was counted."
        );
    }
}
