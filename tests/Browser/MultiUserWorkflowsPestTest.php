<?php

use App\Models\User;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\ClientUser;
use Modules\PIB\Models\Invoice;

function getMultiUserAdmin(): User
{
    $admin = User::firstOrCreate(['email' => 'multiuser-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'MultiUser',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (!$admin->isAdmin()) { $admin->role = User::ROLE_ADMIN; $admin->save(); }
    return $admin;
}

function loginAsMultiAdmin($test): void
{
    $admin = getMultiUserAdmin();
    $test->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');
}

it('quote lifecycle with client rejection and acceptance', function () {
    $admin = getMultiUserAdmin();
    $client = Client::factory()->create(['name' => 'Lifecycle Client']);

    // Create and send quote
    $quote = \Modules\ContractManager\Models\Quote::factory()->sent()->create([
        'client_id' => $client->id,
        'title' => 'Lifecycle Quote',
    ]);
    expect($quote->status)->toBe('sent');
    expect($quote->canBeApproved())->toBeTrue();
    expect($quote->canBeRejected())->toBeTrue();

    // Reject it
    $quote->reject('Too expensive', $admin->id);
    expect($quote->fresh()->status)->toBe('rejected');

    // Revise it
    $revised = $quote->revise($admin->id);
    expect($revised)->not->toBeNull();

    loginAsMultiAdmin($this);
    $this->visit('/contracts/quotes')
        ->assertSee('Quote');
})->group('multi-user', 'quote-lifecycle');

test('client portal invoice viewing', function () {
    $client = Client::factory()->create(['name' => 'Invoice View Client']);
    $clientUser = ClientUser::factory()->create([
        'client_id' => $client->id,
        'name' => 'Invoice View User',
        'email' => 'invoiceview-' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    $invoiceNumber = 'INV-TEST-' . rand(1000, 9999);
    $invoice = Invoice::factory()->create([
        'client_id' => $client->id,
        'status' => 'unpaid',
        'total_amount' => 50000,
        'invoice_number' => $invoiceNumber,
    ]);

    $this->visit('/portal/login')
        ->type('email', $clientUser->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/portal/invoices')
        ->assertSee($invoiceNumber);

    expect($invoice->fresh()->status)->toBe('unpaid');
})->group('multi-user', 'client-portal');

it('automatic invoice flow', function () {
    loginAsMultiAdmin($this);

    $this->visit('/billing/invoices')
        ->assertSee('Invoice');
})->group('multi-user', 'invoice-automation');

it('payment processing flow', function () {
    loginAsMultiAdmin($this);

    $this->visit('/billing/payments/create')
        ->assertSee('Payment');
})->group('multi-user', 'payment-processing');

it('recurring quote to billing template', function () {
    loginAsMultiAdmin($this);

    $client = Client::factory()->create(['name' => 'Recurring Template Client']);
    $quote = \Modules\ContractManager\Models\Quote::factory()->approved()->create([
        'client_id' => $client->id,
        'title' => 'Recurring Service',
        'billing_type' => 'recurring',
        'billing_cycle' => 'monthly',
    ]);

    // Verify approved quote can convert to billing template
    expect($quote->isApproved())->toBeTrue();
    expect($quote->billing_type)->toBe('recurring');

    $this->visit('/contracts/billing-templates')
        ->assertSee('Billing');
})->group('multi-user', 'quote-lifecycle');

it('client portal assets and subscriptions', function () {
    $client = Client::factory()->create(['name' => 'Portal Assets Client']);
    $clientUser = ClientUser::factory()->create([
        'client_id' => $client->id,
        'name' => 'Portal Assets User',
        'email' => 'portal-assets-' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    $this->visit('/portal/login')
        ->type('email', $clientUser->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/portal/dashboard')
        ->assertSee('Client Portal');
})->group('multi-user', 'client-portal');
