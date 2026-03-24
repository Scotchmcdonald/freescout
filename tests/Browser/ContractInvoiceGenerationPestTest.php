<?php

declare(strict_types=1);

use App\Models\User;
use Modules\ContractManager\Models\Contract;
use Modules\Crm\Models\Client;

test('can generate invoice for contract', function () {
    $admin = User::firstOrCreate(['email' => 'contract-admin-'.uniqid().'@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'Contract',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);

    $client = Client::factory()->create(['name' => 'Invoice Test Corp']);

    $contract = Contract::create([
        'client_id' => $client->id,
        'title' => 'Test Service Contract',
        'contract_number' => 'CON-TEST-'.uniqid(),
        'status' => 'active',
        'start_date' => now(),
        'contract_type' => 'standard',
        'monthly_amount' => 100.00,
    ]);

    // Login
    browserLoginAdmin($this, $admin);

    $this->visit("/contracts/agreements/{$contract->id}")
        ->waitForText('Test Service Contract')
        ->assertSee('Active')
        ->click('[dusk="generate-invoice-button"]')
        ->waitForText('Invoice generated');
});

test('rent to own stops at purchase cap', function () {
    $admin = User::firstOrCreate(['email' => 'rto-admin-'.uniqid().'@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'RTO',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (! $admin->isAdmin()) {
        $admin->role = User::ROLE_ADMIN;
        $admin->save();
    }

    $client = Client::factory()->create(['name' => 'RTO Test Corp']);

    $contract = Contract::create([
        'client_id' => $client->id,
        'title' => 'RTO Equipment',
        'contract_number' => 'CON-RTO-'.uniqid(),
        'status' => 'active',
        'start_date' => now(),
        'contract_type' => 'rent_to_own',
        'purchase_price' => 300.00,
        'monthly_rental_fee' => 100.00,
        'asset_description' => 'Test Equipment',
    ]);

    browserLoginAdmin($this, $admin);

    $browser = $this->visit("/contracts/agreements/{$contract->id}")
        ->waitForText('RTO Equipment');

    // Generate first invoice ($100)
    $browser->click('[dusk="generate-invoice-button"]')
        ->waitForText('Invoice generated', 10);

    // Reload to clear toast
    $browser = $this->visit("/contracts/agreements/{$contract->id}");

    // Generate second invoice ($200 total)
    $browser->waitForText('RTO Equipment')
        ->click('[dusk="generate-invoice-button"]')
        ->waitForText('Invoice generated', 10);
    $browser = $this->visit("/contracts/agreements/{$contract->id}");

    // Generate third invoice - final payment ($300 total)
    $browser->waitForText('RTO Equipment')
        ->click('[dusk="generate-invoice-button"]')
        ->waitForText('final payment', 10);
    $browser = $this->visit("/contracts/agreements/{$contract->id}");

    // Generate fourth invoice - should fail (cap reached)
    $browser->waitForText('RTO Equipment')
        ->click('[dusk="generate-invoice-button"]')
        ->waitForText('purchase price cap reached', 10);
});

test('can generate buyout invoice', function () {
    $admin = User::firstOrCreate(['email' => 'buyout-admin-'.uniqid().'@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'Buyout',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (! $admin->isAdmin()) {
        $admin->role = User::ROLE_ADMIN;
        $admin->save();
    }

    $client = Client::factory()->create(['name' => 'Buyout Test Corp']);

    $contract = Contract::create([
        'client_id' => $client->id,
        'title' => 'Buyout Equipment',
        'contract_number' => 'CON-BUYOUT-'.uniqid(),
        'status' => 'active',
        'start_date' => now(),
        'contract_type' => 'rent_to_own',
        'purchase_price' => 500.00,
        'monthly_rental_fee' => 100.00,
        'asset_description' => 'Test Equipment',
        'allow_early_buyout' => true,
    ]);

    browserLoginAdmin($this, $admin);

    $browser = $this->visit("/contracts/agreements/{$contract->id}")
        ->waitForText('Buyout Equipment')
        ->assertVisible('[dusk="generate-invoice-button"]');

    // Generate one regular invoice first
    $browser->click('[dusk="generate-invoice-button"]')
        ->waitForText('Invoice generated');

    $browser = $this->visit("/contracts/agreements/{$contract->id}");

    // Override window.confirm to auto-accept
    $browser->script('window.confirm = () => true');

    // Generate buyout invoice for remaining $400
    $browser->assertVisible('[dusk="generate-buyout-button"]')
        ->click('[dusk="generate-buyout-button"]')
        ->waitForText('Buyout invoice generated')
        ->waitForText('400');
});
