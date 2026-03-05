<?php

use Modules\ContractManager\Models\BillingTemplate;
use Modules\ContractManager\Models\Contract;
use Modules\Crm\Models\Client;
use Modules\PIB\Services\InvoiceGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

beforeEach(function () {
    if (!class_exists(BillingTemplate::class) || !class_exists(InvoiceGenerator::class)) {
        $this->markTestSkipped('Contract/Billing modules not installed');
    }
    
    $this->client = Client::factory()->create();
    $this->generator = app(InvoiceGenerator::class);
});

// From ContractTerminationTest.php
test('terminated contract generates final invoice', function () {
    $client = Client::factory()->create(['name' => 'Churning Client']);
    
    // Contract active from Jan 1
    $contract = Contract::create([
        'client_id' => $client->id,
        'title' => 'Service Agreement',
        'status' => 'active',
        'start_date' => '2024-01-01',
    ]);

    $template = BillingTemplate::create([
        'client_id' => $client->id,
        'contract_id' => $contract->id,
        'name' => 'Monthly Retainer',
        'product_type' => 'service_plan',
        'product_config' => ['base_price' => 1000.00],
        'billing_cycle' => 'monthly',
        'status' => 'active',
        'next_invoice_date' => '2024-02-01', // Due Feb 1
    ]);

    // 2. Churn Event on Jan 15
    Carbon::setTestNow('2024-01-15 12:00:00');
    
    // Terminate Contract and Template. Handle both for safety.
    $contract->update(['status' => 'terminated', 'end_date' => now()]);
    $template->update(['status' => 'terminated']);

    // 3. Billing Run on Feb 1
    Carbon::setTestNow('2024-02-01 12:00:00');
    
    // We simulate the cron job grabbing "Due Templates"
    // Note: We use the actual service method used by the cron
    $dueTemplates = $this->generator->getDueTemplates();
    
    // The ID of our churning template should be in the list OR 
    // a specific "Final Bill" process should have run.
    // If it's not in the list, no invoice is generated.
    
    // Fails if the system ignores terminated templates with outstanding balances
    expect($dueTemplates->contains('id', $template->id))
        ->toBeTrue("The billing engine ignored the terminated contract, resulting in revenue leakage.");

    // If we manually force generation (simulating a 'final bill' action):
    $invoice = $this->generator->generateFromTemplate($template, now(), Carbon::parse('2024-01-01'), Carbon::parse('2024-01-15'));
    
    expect($invoice)->not->toBeNull();
});

// From PlanChangeTest.php
test('mid cycle plan change is handled correctly', function () {
     // 1. Setup - Jan 1
    $client = Client::factory()->create();
    
    $template = BillingTemplate::create([
        'client_id' => $client->id,
        'name' => 'Service Plan',
        'product_type' => 'service_plan',
        'product_config' => ['base_price' => 100.00], // Plan A
        'billing_cycle' => 'monthly',
        'status' => 'active',
        'next_invoice_date' => '2024-02-01',
    ]);

    // 2. Mid-Month Change - Jan 15
    Carbon::setTestNow('2024-01-15 12:00:00');
    
    // Upgrade to $200 Plan
    $template->update([
        'product_config' => ['base_price' => 200.00]
    ]);

    // 3. Billing Run - Feb 1
    Carbon::setTestNow('2024-02-01');
    
    // Explicitly bill for January (Arrears) to capture the change on Jan 15
    $startDate = Carbon::parse('2024-01-01');
    $endDate = Carbon::parse('2024-01-31')->endOfDay();
    
    $invoice = $this->generator->generateFromTemplate($template, now(), $startDate, $endDate);

    // 4. Verification
    // Expected Math (Prorated):
    // 15 Days @ $100 + 16 Days @ $200
    // (100 * 0.48) + (200 * 0.52) = $48 + $104 = ~$152
    
    $amount = $invoice->total_amount;
    
    expect($amount)->not->toEqual(200.00); // Bug: Full month at new upgraded price
    expect($amount)->not->toEqual(100.00); // Bug: Full month at old price
    
    // Allow some float variance
    expect($amount)->toEqualWithDelta(150.00, 10.00); 
});

// From BillingEdgeCasesTest.php
test('invoice numbers are globally unique', function () {
    $client2 = Client::factory()->create();
    
    $template1 = BillingTemplate::create([
        'client_id' => $this->client->id,
        'name' => 'Template 1',
        'product_type' => 'service_plan',
        'product_config' => ['base_price' => 500.00],
        'billing_cycle' => 'monthly',
        'next_invoice_date' => now(),
        'status' => 'active',
    ]);   
    
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
    
    expect($invoice1->invoice_number)->not->toEqual($invoice2->invoice_number);
    
    // Verify sequence
    expect($invoice1->invoice_number)->toContain('0001');
    expect($invoice2->invoice_number)->toContain('0002');
});
