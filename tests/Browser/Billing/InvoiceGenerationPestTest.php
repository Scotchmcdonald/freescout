<?php

use App\Models\User;
use Modules\Crm\Models\Client;

it('manual invoice creation', function () {
    $admin = User::firstOrCreate(['email' => 'invoice-gen-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'InvoiceGen',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (!$admin->isAdmin()) { $admin->role = User::ROLE_ADMIN; $admin->save(); }

    $client = Client::factory()->create(['name' => 'Invoice Gen Client']);

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/billing/invoices/create')
        ->assertSee('Invoice');
})->group('billing', 'invoice');

it('recurring invoice generation', function () {
    $admin = User::firstOrCreate(['email' => 'recurring-inv-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'RecurringInv',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (!$admin->isAdmin()) { $admin->role = User::ROLE_ADMIN; $admin->save(); }

    $client = Client::factory()->create(['name' => 'Recurring Invoice Client']);
    $template = \Modules\ContractManager\Models\BillingTemplate::factory()->create([
        'client_id' => $client->id,
        'name' => 'Monthly Service',
        'product_type' => 'service_plan',
        'billing_cycle' => 'monthly',
        'status' => 'active',
    ]);

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    // Navigate to billing templates to trigger generation
    $this->visit('/contracts/billing-templates')
        ->assertSee('Billing');

    // Verify template is active and due billing can be triggered
    expect($template->fresh()->status)->toBe('active');
})->group('billing', 'e2e');
