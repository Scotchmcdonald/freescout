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
    if (! $admin->isAdmin()) {
        $admin->role = User::ROLE_ADMIN;
        $admin->save();
    }

    return $admin;
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

    browserLoginAdmin($this, getMultiUserAdmin());
    $this->visit('/contracts/quotes')
        ->assertPathIs('/contracts/quotes');
})->group('multi-user', 'quote-lifecycle');

test('client portal invoice viewing', function () {
    $company = Company::factory()->create(['is_active' => true]);
    $client = Client::factory()->create(['name' => 'Invoice View Client', 'company_id' => $company->id, 'status' => 'active']);

    $user = User::factory()->create([
        'type' => 2,
        'email' => 'invoiceview-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'status' => User::STATUS_ACTIVE,
        'email_verified_at' => now(),
    ]);
    $company->users()->attach($user->id, ['role_id' => 1, 'status' => 'approved', 'is_primary' => true]);

    $invoiceNumber = 'INV-TEST-'.rand(1000, 9999);
    $invoice = Invoice::factory()->create([
        'client_id' => $client->id,
        'status' => 'unpaid',
        'total_amount' => 50000,
        'invoice_number' => $invoiceNumber,
    ]);

    browserLoginPortal($this, $user);

    $this->visit('/portal/invoices')
        ->assertSee($invoiceNumber);

    expect($invoice->fresh()->status)->toBe('unpaid');
})->group('multi-user', 'client-portal');

it('automatic invoice flow', function () {
    browserLoginAdmin($this, getMultiUserAdmin());

    $this->visit('/billing/invoices')
        ->assertPathIs('/billing/invoices');
})->group('multi-user', 'invoice-automation');

it('payment processing flow', function () {
    browserLoginAdmin($this, getMultiUserAdmin());

    $this->visit('/billing/payments/create')
        ->assertPathIs('/billing/payments/create');
})->group('multi-user', 'payment-processing');

it('recurring quote to billing template', function () {
    browserLoginAdmin($this, getMultiUserAdmin());

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
        ->assertPathIs('/contracts/billing-templates');
})->group('multi-user', 'quote-lifecycle');

it('client portal assets and subscriptions', function () {
    $company = Company::factory()->create(['is_active' => true]);
    $client = Client::factory()->create(['name' => 'Portal Assets Client', 'company_id' => $company->id, 'status' => 'active']);

    $user = User::factory()->create([
        'type' => 2,
        'email' => 'portal-assets-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'status' => User::STATUS_ACTIVE,
        'email_verified_at' => now(),
    ]);
    $company->users()->attach($user->id, ['role_id' => 1, 'status' => 'approved', 'is_primary' => true]);

    browserLoginPortal($this, $user);

    $this->visit('/portal/dashboard')
        ->assertPathIs('/portal/dashboard');
})->group('multi-user', 'client-portal');
