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

class RevenueLeakageTest extends TestCase
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
     * CRITICAL REVENUE LEAK TEST
     * 
     * If an asset is retired/removed mid-month, it MUST be billed for the days it was active.
     * Currently, 'active' filters miss these assets completely.
     */
    public function test_retired_asset_billing_leak(): void
    {
        $template = $this->createProratedTemplate();
        
        // 1. Create an asset active from Jan 1 to Jan 20
        $asset = Asset::create([
            'client_id' => $this->client->id,
            'asset_type' => 'workstation',
            'status' => 'retired', // Currently retired
            'serial_number' => 'LEAK-TEST-001',
            'source' => 'manual',
        ]);
        
        // Backdate creation to Jan 1
        // Backdate update (retirement) to Jan 20
        DB::table($asset->getTable())->where('id', $asset->id)->update([
            'created_at' => Carbon::parse('2024-01-01'),
            'updated_at' => Carbon::parse('2024-01-20')->endOfDay(),
        ]);

        // 2. Run Billing for Jan 1 - Jan 31
        $invoice = $this->generator->generateFromTemplate(
            $template, 
            Carbon::parse('2024-02-01'), // Invoice Date
            Carbon::parse('2024-01-01'), // Period Start
            Carbon::parse('2024-01-31')  // Period End
        );

        // 3. Assert
        // Should pay for 20 days. 
        // 20/31 * $100 = $64.51
        
        $this->assertGreaterThan(
            0, 
            $invoice->total_amount, 
            "REVENUE LEAK DETECTED: Retired assets are not being billed for their active period."
        );
        
        $expected = (20 / 31) * 100;
        $this->assertEqualsWithDelta($expected, $invoice->total_amount, 1.0, "Should be billed for approx 20 days");
    }

    /**
     * Ensure we don't bill for pending or rejected service usage
     */
    public function test_unapproved_service_usage_is_ignored(): void
    {
        $template = $this->createStandardTemplate();
        
        // Pending
        ServiceUsage::create([
            'client_id' => $this->client->id,
            'service_type' => ServiceUsage::TYPE_LABOR,
            'hours' => 5.0,
            'hourly_rate' => 100.00,
            'status' => 'pending', // NOT approved
            'service_date' => now(), // Required
        ]);
        
        // Rejected
        ServiceUsage::create([
            'client_id' => $this->client->id,
            'service_type' => ServiceUsage::TYPE_LABOR,
            'hours' => 10.0,
            'hourly_rate' => 100.00,
            'status' => 'rejected', // NOT approved
            'service_date' => now(), // Required
        ]);

        $invoice = $this->generator->generateFromTemplate($template);

        // Should only be Base Price ($500)
        $this->assertEquals(500.00, $invoice->total_amount);
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
}
