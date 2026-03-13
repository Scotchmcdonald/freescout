<?php

use App\Models\User;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\Company;
use Modules\Payment\Models\Payment;
use Modules\PIB\Models\Invoice;

/**
 * Browser tests for client portal invoice views.
 * Uses pestphp/pest-plugin-browser (Playwright driver).
 */
function createPortalTestData(): array
{
    $company = Company::factory()->create(['is_active' => true]);
    $client = Client::factory()->create([
        'company_id' => $company->id,
        'name' => 'Portal Browser Client',
        'status' => 'active',
    ]);

    $user = User::factory()->create([
        'type' => 2,
        'email' => 'portalbrowser-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'status' => User::STATUS_ACTIVE,
        'email_verified_at' => now(),
    ]);
    $company->users()->attach($user->id, ['role_id' => 1, 'status' => 'approved', 'is_primary' => true]);

    return [$user, $client, $company];
}

function loginPortalUser($test, User $user): void
{
    $test->visit('/portal/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');
}

it('displays invoice with tabs and PDF download link', function () {
    [$user, $client, $company] = createPortalTestData();

    $invoice = Invoice::create([
        'client_id' => $client->id,
        'company_id' => $company->id,
        'total_amount' => 850.00,
        'subtotal' => 850.00,
        'tax_amount' => 0,
        'amount_paid' => 0,
        'invoice_number' => 'INV-PTAB-001',
        'invoice_date' => now(),
        'due_date' => now()->addDays(30),
        'status' => 'sent',
        'metadata' => [],
    ]);

    loginPortalUser($this, $user);

    $this->visit("/portal/invoices/{$invoice->id}")
        ->assertSee('INV-PTAB-001')
        ->assertSee('Download PDF')
        ->assertSee('Pay Now')
        ->assertSee('Summary')
        ->assertSee('Line Items')
        ->assertSee('Timeline');
})->group('portal', 'invoice', 'browser');

it('shows payments tab when payments exist', function () {
    [$user, $client, $company] = createPortalTestData();

    $invoice = Invoice::create([
        'client_id' => $client->id,
        'company_id' => $company->id,
        'total_amount' => 500.00,
        'subtotal' => 500.00,
        'tax_amount' => 0,
        'amount_paid' => 200.00,
        'invoice_number' => 'INV-PPAY-001',
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
        'payment_type' => 'card',
        'card_brand' => 'Visa',
        'last_four' => '4242',
        'transaction_type' => 'purchase',
        'processed_at' => now(),
        'invoice_number' => 'INV-PPAY-001',
    ]);

    loginPortalUser($this, $user);

    $this->visit("/portal/invoices/{$invoice->id}")
        ->assertSee('INV-PPAY-001')
        ->assertSee('Payments (1)')
        ->click('text=Payments (1)')
        ->assertSee('$200.00');
})->group('portal', 'invoice', 'payments', 'browser');

it('shows paid status banner for paid invoices', function () {
    [$user, $client, $company] = createPortalTestData();

    $invoice = Invoice::create([
        'client_id' => $client->id,
        'company_id' => $company->id,
        'total_amount' => 300.00,
        'subtotal' => 300.00,
        'tax_amount' => 0,
        'amount_paid' => 300.00,
        'invoice_number' => 'INV-PPAID-001',
        'invoice_date' => now(),
        'due_date' => now()->addDays(30),
        'status' => 'paid',
        'paid_at' => now(),
        'metadata' => [],
    ]);

    loginPortalUser($this, $user);

    $this->visit("/portal/invoices/{$invoice->id}")
        ->assertSee('INV-PPAID-001')
        ->assertSee('Paid')
        ->assertDontSee('Pay Now');
})->group('portal', 'invoice', 'browser');

it('shows pay invoice page with payment methods and correct amount', function () {
    [$user, $client, $company] = createPortalTestData();

    $invoice = Invoice::create([
        'client_id' => $client->id,
        'company_id' => $company->id,
        'total_amount' => 750.00,
        'subtotal' => 750.00,
        'tax_amount' => 0,
        'amount_paid' => 250.00,
        'invoice_number' => 'INV-PPAYFORM-001',
        'invoice_date' => now(),
        'due_date' => now()->addDays(30),
        'status' => 'partially_paid',
        'metadata' => [],
    ]);

    // Create a payment method for the company
    \Modules\Payment\Models\PaymentMethod::create([
        'company_id' => $company->id,
        'helcim_customer_id' => 'CUST-PAY-'.uniqid(),
        'helcim_card_token' => 'TOKEN-PAY-'.uniqid(),
        'last_four' => '9876',
        'card_brand' => 'MasterCard',
        'card_type' => 'credit',
        'expiry_month' => '12',
        'expiry_year' => '2028',
        'cardholder_name' => 'Portal Test User',
        'is_default' => true,
        'is_active' => true,
        'status' => 'active',
        'verified_at' => now(),
    ]);

    loginPortalUser($this, $user);

    $this->visit("/portal/invoices/{$invoice->id}/pay")
        ->assertSee('Pay Invoice')
        ->assertSee('$500.00')   // outstanding balance (750 - 250)
        ->assertSee('9876')       // payment method last four
        ->assertSee('MasterCard')
        ->assertSee('Pay $500.00 Now');
})->group('portal', 'pay-invoice', 'browser');
