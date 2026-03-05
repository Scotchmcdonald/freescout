<?php

use Carbon\Carbon;
use Modules\ContractManager\Models\BillingTemplate;
use Modules\Crm\Models\Client;
use Modules\PIB\Services\InvoiceGenerator;
use Modules\AssetManagement\Entities\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    if (!class_exists(Asset::class)) {
        $this->markTestSkipped('AssetManagement module not available');
    }
    
    $this->generator = app(InvoiceGenerator::class);
    // Be careful with Client creation in beforeEach if tests override it
    $this->client = Client::factory()->create();
});

// From AssetTransferBillingTest.php
test('invoice generation handles mid period asset transfer', function () {
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
        'asset_type' => 'chromebook',
        'source' => 'manual',
    ]);

    // 4. Move Asset to Client B on Jan 15th
    Carbon::setTestNow('2024-01-15 12:00:00'); 
    $asset->update(['client_id' => $clientB->id]);

    // 5. Generate Invoices for Jan 1 - Jan 31
    Carbon::setTestNow('2024-02-01');
    $startDate = Carbon::parse('2024-01-01');
    $endDate = Carbon::parse('2024-01-31')->endOfDay();

    $invoiceA = $this->generator->generateFromTemplate($templateA, now(), $startDate, $endDate);
    
    Carbon::setTestNow(now()->addSecond());
    $invoiceB = $this->generator->generateFromTemplate($templateB, now(), $startDate, $endDate);

    // 6. Assertions for Client A
    $lineItemA = $invoiceA->lineItems->first(function($item) {
        return str_contains($item->description, 'Per-Asset');
    });
    
    expect($lineItemA)->not->toBeNull("Revenue Leakage: Client A lost billing record for transferred asset");
    expect((float)$lineItemA->quantity)->toBeBetween(0.40, 0.60); // Approx half month

    // 7. Assertions for Client B
    $lineItemB = $invoiceB->lineItems->first(function($item) {
        return str_contains($item->description, 'Per-Asset');
    });
    
    expect((float)$lineItemB->quantity)->toBeBetween(0.40, 0.60);
});

// From ProrationIntegrationTest.php
test('mid month asset addition is prorated', function () {
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
    // We simulate "Time Travel" by creating an asset and backdating it
    $asset = Asset::create([
        'client_id' => $this->client->id,
        'name' => 'Mid-Month Laptop',
        'asset_type' => 'workstation',
        'status' => 'active',
        'serial_number' => 'ABC-123',
        'source' => 'manual',
    ]);
    
    // Force timestamp update at database level
    DB::table($asset->getTable())->where('id', $asset->id)->update([
        'created_at' => Carbon::parse('2024-01-15'),
        'updated_at' => Carbon::parse('2024-01-15'),
    ]);
    
    $asset->refresh();

    // 3. Generate Invoice for Period: Jan 1 - Jan 31
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
    expect($invoice)->not->toBeNull();
    
    // We expect Proration to be APPLIED (fail if it charges full price)
    // Full month would be 100. Prorated (approx half) should be ~54.84
    expect($invoice->total_amount)->toBeLessThan(100.00);
    expect($invoice->total_amount)->toBeGreaterThan(50.00);
});


// From RevenueLeakageTest.php
test('retired asset billing leak', function () {
    $template = BillingTemplate::create([
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
    
    expect($invoice->total_amount)->toBeGreaterThan(0, "REVENUE LEAK DETECTED: Retired assets are not being billed for their active period.");
    
    $expected = (20 / 31) * 100;
    expect($invoice->total_amount)->toEqualWithDelta($expected, 1.0);
});

// From BillingEdgeCasesTest.php
test('proration first day full charge', function () {
    $template = BillingTemplate::create([
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
    
    // Asset created exactly at start of period
    $asset = Asset::create([
        'client_id' => $this->client->id, 
        'asset_type' => 'windows', 
        'status' => 'active', 
        'serial_number' => 'A1', 
        'source' => 'manual'
    ]);
    DB::table($asset->getTable())->where('id', $asset->id)->update([
        'created_at' => Carbon::parse('2024-01-01'),
        'updated_at' => Carbon::parse('2024-01-01'),
    ]);

    $invoice = $this->generator->generateFromTemplate(
        $template,
        Carbon::parse('2024-02-01'), 
        Carbon::parse('2024-01-01'), 
        Carbon::parse('2024-01-31')->endOfDay()
    );

    // Should be exactly $100
    expect($invoice->total_amount)->toEqual(100.00);
});

test('proration last day charge', function () {
    $template = BillingTemplate::create([
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
    
    // Asset created on Jan 31st
    $asset = Asset::create([
        'client_id' => $this->client->id, 'asset_type' => 'windows', 'status' => 'active', 'serial_number' => 'A31', 'source' => 'manual'
    ]);
    DB::table($asset->getTable())->where('id', $asset->id)->update([
        'created_at' => Carbon::parse('2024-01-31'),
        'updated_at' => Carbon::parse('2024-01-31'),
    ]);

    $invoice = $this->generator->generateFromTemplate(
        $template,
        Carbon::parse('2024-02-01'), 
        Carbon::parse('2024-01-01'), 
        Carbon::parse('2024-01-31')->endOfDay()
    );

    // 1 day out of 31 days * $100 = 3.2258...
    $expected = (1 / 31) * 100;
    
    expect($invoice->total_amount)->toEqualWithDelta($expected, 0.02);
});
