<?php

use Modules\Crm\Models\Client;
use Modules\Crm\Models\ClientUser;
use Modules\PIB\Models\Invoice;

it('payment method ui management', function () {
    $client = Client::factory()->create(['name' => 'Payment UI Client']);
    $clientUser = ClientUser::factory()->create([
        'client_id' => $client->id,
        'name' => 'Payment UI User',
        'email' => 'paymentui-' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    $this->visit('/portal/login')
        ->type('email', $clientUser->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/portal/payments')
        ->assertSee('Payment Methods');
})->group('billing', 'payment', 'ui');

test('failed payment retry ui', function () {
    $client = Client::factory()->create(['name' => 'Broke Corp']);
    $clientUser = ClientUser::factory()->create([
        'client_id' => $client->id,
        'name' => 'Broke User',
        'email' => 'broke-' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    $invoice = Invoice::create([
        'client_id' => $client->id,
        'company_id' => $client->company_id ?? 1,
        'status' => 'past_due',
        'total_amount' => 100.00,
        'invoice_date' => now(),
        'due_date' => now()->subDays(5),
        'invoice_number' => 'INV-FAIL-' . uniqid(),
    ]);

    $this->visit('/portal/login')
        ->type('email', $clientUser->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/portal/invoices')
        ->assertSee('Overdue')
        ->click("text={$invoice->invoice_number}")
        ->waitForText('Payment Due')
        ->assertSee('Pay Now');
})->group('billing', 'payment', 'retry');
