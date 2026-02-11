<?php

use App\Models\User;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\Company;
use Modules\Payment\Services\ClientCreditService;
use Modules\Payment\Models\ClientCreditLedger;
use Modules\Payment\Models\Payment;
use Modules\PIB\Models\Invoice;

// Helper to get/create admin
function getCreditAdmin() {
    return User::firstOrCreate(['email' => 'credit-admin@example.com'], [
        'role' => User::ROLE_ADMIN,
        'password' => bcrypt('password'),
        'first_name' => 'Credit',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
}

test('payment creates credit and ledger', function () {
    $admin = getCreditAdmin();
    $client = Client::factory()->create();
    
    // Auth context for 'created_by' audit logic
    $this->actingAs($admin);
    
    $creditService = app(ClientCreditService::class);
    
    // 1. Add Credit
    $creditService->addCredit($client, 100.00, 'Test Credit Deposit');
    
    // Verify balance
    expect($creditService->getBalance($client))->toEqual(100.00);
    
    // 2. Deduct Credit
    $creditService->deductCredit($client, 25.00, 'Test Usage');
    
    // Verify new balance
    expect($creditService->getBalance($client))->toEqual(75.00);
    
    // 3. Check Ledger Entries
    $entries = ClientCreditLedger::where('client_id', $client->id)->orderBy('id')->get();
    
    expect($entries)->toHaveCount(2);
    expect($entries[0]->amount)->toEqual(100.00);
    expect($entries[0]->transaction_type)->toEqual('credit');
    expect($entries[1]->amount)->toEqual(-25.00);
    expect($entries[1]->transaction_type)->toEqual('debit');
})->group('credits', 'payment', 'audit');


test('credit application scenarios', function () {
    // 1. Setup Data
    $client = Client::factory()->create();
    // Ensure client has company_id
    if (!$client->company_id) {
         $company = Company::factory()->create();
         $client->company_id = $company->id;
         $client->save();
    }

    $creditService = app(ClientCreditService::class);
    
    // Add $500 Credit
    $creditService->addCredit($client, 500.00, 'Initial Deposit');
    
    // Create 3 Invoices
    $invoicePartial = Invoice::factory()->create([
        'client_id' => $client->id,
        'company_id' => $client->company_id,
        'total_amount' => 100.00,
        'status' => 'sent'
    ]);
    
    $invoiceFull = Invoice::factory()->create([
        'client_id' => $client->id,
        'company_id' => $client->company_id,
        'total_amount' => 200.00,
        'status' => 'sent'
    ]);

    $invoiceOver = Invoice::factory()->create([
        'client_id' => $client->id,
        'company_id' => $client->company_id,
        'total_amount' => 50.00,
        'status' => 'sent'
    ]);

    // Scenario A: Partial Payment (Equality Check: Credit > Amount Applied)
    // Apply $50 to $100 invoice
    $creditService->applyToInvoice($client, $invoicePartial, 50.00);
    
    expect($creditService->getBalance($client))->toEqual(450.00);
    expect($invoicePartial->fresh()->status)->toEqual('sent');
    
    // Verify Payment Record
    expect(Payment::where('invoice_id', $invoicePartial->id)->sum('amount'))->toEqual(50.00);

    // Scenario B: Full Payment (Equality Check: Credit == Amount)
    // Apply remaining $50 to partial invoice (Total $100)
    $creditService->applyToInvoice($client, $invoicePartial, 50.00);
    
    expect($creditService->getBalance($client))->toEqual(400.00);
    expect($invoicePartial->fresh()->status)->toEqual('paid');
    expect($invoicePartial->fresh()->paid_at)->not->toBeNull();

    // Scenario C: One-Shot Full Payment
    $creditService->applyToInvoice($client, $invoiceFull, 200.00);
    expect($creditService->getBalance($client))->toEqual(200.00);
    expect($invoiceFull->fresh()->status)->toEqual('paid');

    // Scenario D: Overpayment Protection
    try {
        $creditService->applyToInvoice($client, $invoiceOver, 60.00);
        $this->fail("Should not allow overpayment of invoice.");
    } catch (\Exception $e) {
        expect($e->getMessage())->toContain('exceeds invoice balance due');
    }

    // Scenario E: Overdraft Protection
    $largeInvoice = Invoice::factory()->create([
        'client_id' => $client->id,
        'company_id' => $client->company_id,
        'total_amount' => 1000.00,
        'status' => 'sent'
    ]);

    try {
        $creditService->applyToInvoice($client, $largeInvoice, 300.00);
        $this->fail("Should not allow overdraft of client credit.");
    } catch (\Exception $e) {
        expect($e->getMessage())->toContain('Insufficient credit balance');
    }
})->group('credits', 'invoices', 'payment');


test('negative balance prevention', function () {
    $client = Client::factory()->create();
    $creditService = app(ClientCreditService::class);
    
    $creditService->addCredit($client, 50.00, 'Deposit');
    
    try {
        $creditService->deductCredit($client, 100.00, 'Too expensive');
        $this->fail('Should have thrown exception for insufficient funds');
    } catch (\Exception $e) {
        expect($e->getMessage())->toContain('Insufficient credit balance');
    }
    
    expect($creditService->getBalance($client))->toEqual(50.00);
})->group('credits', 'validation', 'error-handling');


test('complete audit trail', function () {
    $client = Client::factory()->create();
    $creditService = app(ClientCreditService::class);
    $user = getCreditAdmin();
    
    $this->actingAs($user);

    // 1. Add Credit
    $creditService->addCredit($client, 100.00, 'Manual Deposit');

    // 2. Deduct Credit using User as reference
    $reference = $user;
    $creditService->deductCredit($client, 40.00, 'Payment for Service', $reference);

    // 3. Verify Ledger Integrity
    $entries = ClientCreditLedger::where('client_id', $client->id)->orderBy('id')->get();
    
    expect($entries)->toHaveCount(2);
    
    $entry1 = $entries[0];
    expect($entry1->amount)->toEqual(100.00);
    expect($entry1->balance_after)->toEqual(100.00);
    expect($entry1->created_by)->toEqual($user->id);
    
    $entry2 = $entries[1];
    expect($entry2->amount)->toEqual(-40.00);
    expect($entry2->balance_after)->toEqual(60.00);
    expect($entry2->created_by)->toEqual($user->id);
    
    // Depending on polymorphic structure, reference_type might be class name
    expect($entry2->reference_type)->toEqual(get_class($reference));
    expect($entry2->reference_id)->toEqual($reference->id);
})->group('credits', 'audit', 'compliance');


test('concurrent credit operations', function () {
    $client = Client::factory()->create();
    $creditService = app(ClientCreditService::class);
    
    $creditService->addCredit($client, 1000.00, 'Initial Funding');
    
    // Simulate 10 rapid deductions
    for ($i = 0; $i < 10; $i++) {
        $creditService->deductCredit($client, 10.00, "Deduction $i");
    }
    
    $finalBalance = $creditService->getBalance($client);
    expect($finalBalance)->toEqual(900.00);
    
    $entryCount = ClientCreditLedger::where('client_id', $client->id)->count();
    expect($entryCount)->toEqual(11); // 1 add + 10 deductions
})->group('credits', 'concurrency');
