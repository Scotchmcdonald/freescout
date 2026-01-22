<?php

/**
 * Credit System Workflow Tests
 * 
 * Validates complete credit lifecycle from payment receipt to invoice application.
 * Financial transactions must be atomic, accurate, and auditable.
 * 
 * PRIORITY: ⭐⭐⭐⭐ (High - Financial Compliance)
 * 
 * RUNNING TESTS:
 * php artisan dusk tests/Browser/CreditSystemWorkflowTest.php
 * php artisan dusk --group=credits
 * php artisan dusk --group=payment
 */

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\DuskTestCase;

class CreditSystemWorkflowTest extends DuskTestCase
{
    protected function getAdminUser(): User
    {
        return User::where('email', 'admin@example.com')->orWhere('role', User::ROLE_ADMIN)->firstOrFail();
    }

    #[Group('credits')]
    #[Group('payment')]
    #[Group('audit')]
    public function test_payment_creates_credit_and_ledger(): void
    {
        $admin = $this->getAdminUser();
        $client = \Modules\Crm\Models\Client::factory()->create();
        
        // Use the service directly to test logic
        $creditService = app(\Modules\Payment\Services\ClientCreditService::class);
        
        $this->browse(function (Browser $browser) use ($admin, $client, $creditService) {
            $browser->loginAs($admin)
                ->visit('/dashboard'); // Just to have a session
                
            // 1. Add Credit
            $creditService->addCredit($client, 100.00, 'Test Credit Deposit');
            
            // Verify balance
            $this->assertEquals(100.00, $creditService->getBalance($client));
            
            // 2. Deduct Credit
            $creditService->deductCredit($client, 25.00, 'Test Usage');
            
            // Verify new balance
            $this->assertEquals(75.00, $creditService->getBalance($client));
            
            // 3. Check Ledger Entries
            $entries = \Modules\Payment\Models\ClientCreditLedger::where('client_id', $client->id)->orderBy('id')->get();
            $this->assertCount(2, $entries);
            $this->assertEquals(100.00, $entries[0]->amount);
            $this->assertEquals('credit', $entries[0]->transaction_type);
            $this->assertEquals(-25.00, $entries[1]->amount);
            $this->assertEquals('debit', $entries[1]->transaction_type);
        });
    }

    #[Group('credits')]
    #[Group('invoices')]
    #[Group('payment')]
    public function test_credit_application_scenarios(): void
    {
        // 1. Setup Data
        $client = \Modules\Crm\Models\Client::factory()->create();
        // Ensure client has company_id
        if (!$client->company_id) {
             $company = \Modules\Crm\Models\Company::factory()->create();
             $client->company_id = $company->id;
             $client->save();
        }

        $creditService = app(\Modules\Payment\Services\ClientCreditService::class);
        
        // Add $500 Credit
        $creditService->addCredit($client, 500.00, 'Initial Deposit');
        
        // Create 3 Invoices
        $invoicePartial = \Modules\PIB\Models\Invoice::factory()->create([
            'client_id' => $client->id,
            'company_id' => $client->company_id,
            'total_amount' => 100.00,
            'status' => 'sent'
        ]);
        
        $invoiceFull = \Modules\PIB\Models\Invoice::factory()->create([
            'client_id' => $client->id,
            'company_id' => $client->company_id,
            'total_amount' => 200.00,
            'status' => 'sent'
        ]);

        $invoiceOver = \Modules\PIB\Models\Invoice::factory()->create([
            'client_id' => $client->id,
            'company_id' => $client->company_id,
            'total_amount' => 50.00,
            'status' => 'sent'
        ]);

        // Scenario A: Partial Payment (Equality Check: Credit > Amount Applied)
        // Apply $50 to $100 invoice
        $creditService->applyToInvoice($client, $invoicePartial, 50.00);
        
        $this->assertEquals(450.00, $creditService->getBalance($client)); // 500 - 50
        $this->assertEquals('sent', $invoicePartial->fresh()->status); // Not fully paid
        
        // Verify Payment Record
        $this->assertEquals(50.00, \Modules\Payment\Models\Payment::where('invoice_id', $invoicePartial->id)->sum('amount'));

        // Scenario B: Full Payment (Equality Check: Credit == Amount)
        // Apply remaining $50 to partial invoice (Total $100)
        $creditService->applyToInvoice($client, $invoicePartial, 50.00);
        
        $this->assertEquals(400.00, $creditService->getBalance($client));
        $this->assertEquals('paid', $invoicePartial->fresh()->status); // Should be paid now
        $this->assertNotNull($invoicePartial->fresh()->paid_at);

        // Scenario C: One-Shot Full Payment
        $creditService->applyToInvoice($client, $invoiceFull, 200.00);
        $this->assertEquals(200.00, $creditService->getBalance($client)); // 400 - 200
        $this->assertEquals('paid', $invoiceFull->fresh()->status);

        // Scenario D: Overpayment Protection (Inequality: Amount > Balance Due)
        // Try to pay $60 on a $50 invoice
        try {
            $creditService->applyToInvoice($client, $invoiceOver, 60.00);
            $this->fail("Should not allow overpayment of invoice.");
        } catch (\Exception $e) {
            $this->assertStringContainsString('exceeds invoice balance due', $e->getMessage());
        }

        // Scenario E: Overdraft Protection (Inequality: Amount > Available Credit)
        // Balance is 200. Try to pay 300.
        $largeInvoice = \Modules\PIB\Models\Invoice::factory()->create([
            'client_id' => $client->id,
            'company_id' => $client->company_id,
            'total_amount' => 1000.00,
            'status' => 'sent'
        ]);

        try {
            $creditService->applyToInvoice($client, $largeInvoice, 300.00);
            $this->fail("Should not allow overdraft of client credit.");
        } catch (\Exception $e) {
            $this->assertStringContainsString('Insufficient credit balance', $e->getMessage());
        }
    }


    #[Group('credits')]
    #[Group('validation')]
    #[Group('error-handling')]
    public function test_negative_balance_prevention(): void
    {
        $client = \Modules\Crm\Models\Client::factory()->create();
        $creditService = app(\Modules\Payment\Services\ClientCreditService::class);
        
        $creditService->addCredit($client, 50.00, 'Deposit');
        
        try {
            $creditService->deductCredit($client, 100.00, 'Too expensive');
            $this->fail('Should have thrown exception for insufficient funds');
        } catch (\Exception $e) {
            $this->assertStringContainsString('Insufficient credit balance', $e->getMessage());
        }
        
        $this->assertEquals(50.00, $creditService->getBalance($client));
    }

    #[Group('credits')]
    #[Group('audit')]
    #[Group('compliance')]
    public function test_complete_audit_trail(): void
    {
        $client = \Modules\Crm\Models\Client::factory()->create();
        $creditService = app(\Modules\Payment\Services\ClientCreditService::class);
        $user = $this->getAdminUser();
        
        // Mock Auth for created_by
        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user);
        });
        auth()->login($user);

        // 1. Add Credit
        $ledgerAdd = $creditService->addCredit($client, 100.00, 'Manual Deposit');

        // 2. Deduct Credit (simulate applied to generic model)
        // We use User as reference since Invoice might not be fully seeder-ready
        $reference = $this->getAdminUser(); 
        $ledgerDeduct = $creditService->deductCredit($client, 40.00, 'Payment for Service', $reference);

        // 3. Verify Ledger Integrity
        $entries = \Modules\Payment\Models\ClientCreditLedger::where('client_id', $client->id)->orderBy('id')->get();
        
        $this->assertCount(2, $entries);
        
        $entry1 = $entries[0];
        $this->assertEquals(100.00, $entry1->amount);
        $this->assertEquals(100.00, $entry1->balance_after);
        $this->assertEquals($user->id, $entry1->created_by);
        
        $entry2 = $entries[1];
        $this->assertEquals(-40.00, $entry2->amount);
        $this->assertEquals(60.00, $entry2->balance_after);
        $this->assertEquals($user->id, $entry2->created_by);
        $this->assertEquals(get_class($reference), $entry2->reference_type);
        $this->assertEquals($reference->id, $entry2->reference_id);
    }

    #[Group('credits')]
    #[Group('concurrency')]
    #[Group('race-conditions')]
    public function test_concurrent_credit_operations(): void
    {
        // This test simulates sequential operations that WOULD fail if balance tracking was loose.
        // True concurrency testing requires parallel processes, but we can verify consistency.
        
        $client = \Modules\Crm\Models\Client::factory()->create();
        $creditService = app(\Modules\Payment\Services\ClientCreditService::class);
        
        $creditService->addCredit($client, 1000.00, 'Initial Funding');
        
        // Simulate 10 rapid deductions
        for ($i = 0; $i < 10; $i++) {
            $creditService->deductCredit($client, 10.00, "Deduction $i");
        }
        
        $finalBalance = $creditService->getBalance($client);
        $this->assertEquals(900.00, $finalBalance);
        
        $entryCount = \Modules\Payment\Models\ClientCreditLedger::where('client_id', $client->id)->count();
        $this->assertEquals(11, $entryCount); // 1 add + 10 deductions
    }

    #[Group('credits')]
    #[Group('smoke')]
    public function test_credit_system_accessible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->getAdminUser())
                ->visit('/dashboard')
                ->assertSee('Dashboard');
        });
    }
}
