<?php

namespace Tests\Feature\Billing;

use Carbon\Carbon;
use Modules\ContractManager\Models\BillingTemplate;
use Modules\Crm\Models\Client;
use Modules\PIB\Services\InvoiceGenerator;
use Modules\AssetManagement\Entities\Asset;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AssetTransferBillingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        if (!class_exists('\Modules\AssetManagement\Entities\Asset')) {
            $this->markTestSkipped('AssetManagement module not available');
        }
    }

    public function test_invoice_generation_handles_mid_period_asset_transfer()
    {
        // 1. Setup Clients
        $clientA = Client::factory()->create(['name' => 'Original Owner']);
        $clientB = Client::factory()->create(['name' => 'New Owner']);

        // 2. Setup Billing Templates
        $templateA = BillingTemplate::create([
            'client_id' => $clientA->id,
            'name' => 'Managed Services A',
            'product_type' => 'service_plan',
            'product_config' => ['plan_name' => 'Plan A', 'base_price' => 100, 'per_asset_price' => 10],
            'billing_cycle' => 'monthly',
            'proration_enabled' => true,
            'status' => 'active',
            'next_invoice_date' => '2024-02-01'
        ]);

        $templateB = BillingTemplate::create([
            'client_id' => $clientB->id,
            'name' => 'Managed Services B',
            'product_type' => 'service_plan',
            'product_config' => ['plan_name' => 'Plan B', 'base_price' => 100, 'per_asset_price' => 10],
            'billing_cycle' => 'monthly',
            'proration_enabled' => true,
            'status' => 'active',
            'next_invoice_date' => '2024-02-01'
        ]);

        // 3. Create Asset for Client A on Jan 1st
        Carbon::setTestNow('2024-01-01');
        $asset = Asset::create([
            'name' => 'Transferred Laptop',
            'client_id' => $clientA->id,
            'status' => 'active',
            'serial_number' => 'TRANSFER-001',
            'asset_type' => 'laptop',
            'source' => 'manual', // Fixed: Added required field
            'created_at' => now(),
        ]);

        // 4. Move Asset to Client B on Jan 15th
        Carbon::setTestNow('2024-01-15 12:00:00'); 
        $asset->update(['client_id' => $clientB->id]);

        // 5. Generate Invoices for Jan 1 - Jan 31
        Carbon::setTestNow('2024-02-01');
        $generator = app(InvoiceGenerator::class);
        $startDate = Carbon::parse('2024-01-01');
        $endDate = Carbon::parse('2024-01-31')->endOfDay();

        $invoiceA = $generator->generateFromTemplate($templateA, now(), $startDate, $endDate);
        
        Carbon::setTestNow(now()->addSecond());
        $invoiceB = $generator->generateFromTemplate($templateB, now(), $startDate, $endDate);

        // 6. Assertions for Client A
        $lineItemA = $invoiceA->lineItems->first(function($item) {
            return str_contains($item->description, 'Per-Asset');
        });
        
        $this->assertNotNull($lineItemA, "Revenue Leakage: Client A lost billing record for transferred asset");
        $this->assertEquals(0.47, round($lineItemA->quantity, 2), "Client A quantity mismatch");

        // 7. Assertions for Client B
        $lineItemB = $invoiceB->lineItems->first(function($item) {
            return str_contains($item->description, 'Per-Asset');
        });

        $this->assertEquals(0.53, round($lineItemB->quantity, 2), "Overbilling: Client B charged for full month");
    }
}
