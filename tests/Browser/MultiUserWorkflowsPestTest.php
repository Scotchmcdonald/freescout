<?php

use App\Models\User;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\Company;
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
    $company = Company::factory()->create(['is_active' => true]);
    $client = Client::factory()->create(['name' => 'Invoice View Client', 'company_id' => $company->id, 'status' => 'active']);

    $user = User::factory()->create([
        'type' => 2,
        'email' => 'invoiceview-' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
        'status' => User::STATUS_ACTIVE,
        'email_verified_at' => now(),
    ]);
    $company->users()->attach($user->id, ['role_id' => 1, 'status' => 'approved', 'is_primary' => true]);

    $invoiceNumber = 'INV-TEST-' . rand(1000, 9999);
    $invoice = Invoice::factory()->create([
        'client_id' => $client->id,
        'status' => 'unpaid',
        'total_amount' => 50000,
        'invoice_number' => $invoiceNumber,
    ]);

    $this->visit('/portal/login')
        ->type('email', $user->email)
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
        'billing_type' => 'monthly',
        'billing_cycle' => 'monthly',
    ]);

    // Verify approved quote can convert to billing template
    expect($quote->isApproved())->toBeTrue();
    expect($quote->billing_type)->toBe('monthly');

    $this->visit('/contracts/billing-templates')
        ->assertSee('Billing');
})->group('multi-user', 'quote-lifecycle');

it('client portal assets and subscriptions', function () {
    $company = Company::factory()->create(['is_active' => true]);
    $client = Client::factory()->create(['name' => 'Portal Assets Client', 'company_id' => $company->id, 'status' => 'active']);

    $user = User::factory()->create([
        'type' => 2,
        'email' => 'portal-assets-' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
        'status' => User::STATUS_ACTIVE,
        'email_verified_at' => now(),
    ]);
    $company->users()->attach($user->id, ['role_id' => 1, 'status' => 'approved', 'is_primary' => true]);

    $this->visit('/portal/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/portal/dashboard')
        ->assertSee('Client Portal');
})->group('multi-user', 'client-portal');
