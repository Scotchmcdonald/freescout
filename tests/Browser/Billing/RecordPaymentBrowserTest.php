<?php

use App\Models\User;
use Modules\PIB\Models\Invoice;
use Modules\Payment\Models\Payment;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\Company;

/**
 * Browser tests for admin record-payment workflow.
 * Uses pestphp/pest-plugin-browser (Playwright driver).
 */

function createRPAdmin(): User
{
    return User::create([
        'email' => 'rp-admin-' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'RecordPay',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
}

function loginAsAdmin($test, User $admin): void
{
    $test->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');
}

it('loads the record-payment form and displays invoice summary', function () {
    $admin = createRPAdmin();
    $company = Company::factory()->create();
    $client = Client::factory()->create(['company_id' => $company->id, 'name' => 'Browser Test Corp']);

    $invoice = Invoice::create([
        'client_id' => $client->id,
        'company_id' => $company->id,
        'total_amount' => 1500.00,
        'amount_paid' => 500.00,
        'subtotal' => 1500.00,
        'tax_amount' => 0,
        'invoice_number' => 'INV-BROWSER-RP',
        'invoice_date' => now(),
        'due_date' => now()->addDays(30),
        'status' => 'partially_paid',
        'metadata' => [],
    ]);

    loginAsAdmin($this, $admin);

    $this->visit("/billing/invoices/{$invoice->id}/record-payment")
        ->assertSee('Record Payment')
        ->assertSee('Browser Test Corp')
        ->assertSee('$1,500.00')   // total
        ->assertSee('$500.00')     // paid
        ->assertSee('$1,000.00');  // outstanding
})->group('billing', 'record-payment', 'browser');

it('shows invoice PDF download button on the show page', function () {
    $admin = createRPAdmin();
    $company = Company::factory()->create();
    $client = Client::factory()->create(['company_id' => $company->id]);

    $invoice = Invoice::create([
        'client_id' => $client->id,
        'company_id' => $company->id,
        'total_amount' => 300.00,
        'amount_paid' => 0,
        'subtotal' => 300.00,
        'tax_amount' => 0,
        'invoice_number' => 'INV-BROWSER-PDF',
        'invoice_date' => now(),
        'due_date' => now()->addDays(30),
        'status' => 'submitted',
        'metadata' => [],
    ]);

    loginAsAdmin($this, $admin);

    $this->visit("/billing/invoices/{$invoice->id}")
        ->assertSee('INV-BROWSER-PDF')
        ->assertSee('PDF')
        ->assertSee('Record Payment');
})->group('billing', 'pdf', 'browser');

it('shows payment history on invoice show page', function () {
    $admin = createRPAdmin();
    $company = Company::factory()->create();
    $client = Client::factory()->create(['company_id' => $company->id, 'name' => 'History Client']);

    $invoice = Invoice::create([
        'client_id' => $client->id,
        'company_id' => $company->id,
        'total_amount' => 500.00,
        'amount_paid' => 200.00,
        'subtotal' => 500.00,
        'tax_amount' => 0,
        'invoice_number' => 'INV-BROWSER-HIST',
        'invoice_date' => now(),
        'due_date' => now()->addDays(30),
        'status' => 'partially_paid',
        'metadata' => [],
    ]);

    Payment::create([
        'company_id' => $company->id,
        'invoice_id' => $invoice->id,
        'amount' => 200.00,
        'fee_amount' => 0,
        'total_amount' => 200.00,
        'status' => 'successful',
        'payment_type' => 'wire',
        'transaction_type' => 'purchase',
        'is_manual' => true,
        'reference_number' => 'WIRE-HIST-001',
        'received_date' => now()->subDays(3),
        'processed_at' => now()->subDays(3),
        'invoice_number' => 'INV-BROWSER-HIST',
    ]);

    loginAsAdmin($this, $admin);

    $this->visit("/billing/invoices/{$invoice->id}")
        ->assertSee('INV-BROWSER-HIST')
        ->assertSee('Record Remaining Payment')
        ->assertSee('$200.00');  // paid amount visible in summary
})->group('billing', 'payment-history', 'browser');
