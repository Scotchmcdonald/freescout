<?php

use Modules\ContractManager\Models\BillingTemplate;
use Modules\Crm\Models\Client;
use Modules\PIB\Models\ServiceUsage;
use Modules\PIB\Services\InvoiceGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

beforeEach(function () {
    if (!class_exists(ServiceUsage::class) || !class_exists(InvoiceGenerator::class)) {
        $this->markTestSkipped('Billing modules not installed');
    }
    
    $this->client = Client::factory()->create();
    $this->generator = app(InvoiceGenerator::class);
});

// From ServiceUsageBillingTest.php
test('unbilled service usage is invoiced', function () {
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
    $expectedTotal = 500.00 + 300.00;

    expect($invoice->total_amount)->toEqual($expectedTotal);

    // Verify line item existence
    $hasLaborLine = $invoice->lineItems()
        ->where('description', 'like', '%Emergency Server Fix%')
        ->exists();
        
    expect($hasLaborLine)->toBeTrue();

    // Verify ServiceUsage record was marked as billed
    $labor->refresh();
    expect($labor->invoice_id)->toBe($invoice->id);
});

// From BillingEdgeCasesTest.php
test('multiple service usage items aggregation', function () {
    $template = BillingTemplate::create([
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
    expect($invoice->total_amount)->toEqual(900.00);
    expect($invoice->lineItems->count())->toBe(3); // Base + Labor + Consult
});

// From BillingEdgeCasesTest.php
test('service usage is billed once only', function () {
    $template = BillingTemplate::create([
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
    
    // Create 1 Billable Item ($100)
    ServiceUsage::create([
        'client_id' => $this->client->id,
        'service_type' => ServiceUsage::TYPE_LABOR,
        'hours' => 1.0,
        'hourly_rate' => 100.00,
        'status' => 'approved',
        'service_date' => now(),
    ]);

    // Run 1
    $invoice1 = $this->generator->generateFromTemplate($template);
    expect($invoice1->total_amount)->toEqual(600.00); // 500 Base + 100 Usage

    // Run 2 (Same period)
    $invoice2 = $this->generator->generateFromTemplate($template);
    
    // usage item is now attached to invoice1, so it should NOT be in invoice2
    expect($invoice2->total_amount)->toEqual(500.00);
});

// From RevenueLeakageTest.php (unapproved part)
test('unapproved service usage is ignored', function () {
    $template = BillingTemplate::create([
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
    
    // Pending
    ServiceUsage::create([
        'client_id' => $this->client->id,
        'service_type' => ServiceUsage::TYPE_LABOR,
        'hours' => 5.0,
        'hourly_rate' => 100.00,
        'status' => 'pending', // NOT approved
        'service_date' => now(),
    ]);
    
    // Rejected
    ServiceUsage::create([
        'client_id' => $this->client->id,
        'service_type' => ServiceUsage::TYPE_LABOR,
        'hours' => 10.0,
        'hourly_rate' => 100.00,
        'status' => 'rejected', // NOT approved
        'service_date' => now(),
    ]);

    $invoice = $this->generator->generateFromTemplate($template);

    // Should only be Base Price ($500)
    expect($invoice->total_amount)->toEqual(500.00);
});
