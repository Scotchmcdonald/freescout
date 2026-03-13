<?php

use App\Models\User;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\Company;
use Modules\PIB\Models\Invoice;

function createPaymentPortalUser(string $clientName, string $emailPrefix): array
{
    $company = Company::factory()->create(['is_active' => true]);
    $client = Client::factory()->create(['company_id' => $company->id, 'name' => $clientName, 'status' => 'active']);
    $user = User::factory()->create([
        'type' => 2,
        'first_name' => $clientName,
        'last_name' => 'User',
        'email' => $emailPrefix.'-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'status' => User::STATUS_ACTIVE,
        'email_verified_at' => now(),
    ]);
    $company->users()->attach($user->id, ['role_id' => 1, 'status' => 'approved', 'is_primary' => true]);

    return [$user, $client, $company];
}

it('payment method ui management', function () {
    [$user] = createPaymentPortalUser('Payment UI Client', 'paymentui');

    $this->visit('/portal/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/portal/payments')
        ->assertSee('Payment Methods');
})->group('billing', 'payment', 'ui');

test('failed payment retry ui', function () {
    [$user, $client, $company] = createPaymentPortalUser('Broke Corp', 'broke');

    $invoice = Invoice::create([
        'client_id' => $client->id,
        'company_id' => $company->id,
        'status' => 'past_due',
        'total_amount' => 100.00,
        'invoice_date' => now(),
        'due_date' => now()->subDays(5),
        'invoice_number' => 'INV-FAIL-'.uniqid(),
    ]);

    $this->visit('/portal/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/portal/invoices')
        ->assertSee('Overdue')
        ->click("text={$invoice->invoice_number}")
        ->waitForText('Payment Due')
        ->assertSee('Pay Now');
})->group('billing', 'payment', 'retry');
