<?php

use App\Models\Customer;
use App\Models\User;

function getCustMgmtAdmin(): User
{
    $admin = User::firstOrCreate(['email' => 'custmgmt-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'CustMgmtTest',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (! $admin->isAdmin()) {
        $admin->role = User::ROLE_ADMIN;
        $admin->save();
    }

    return $admin;
}

it('customer list page loads', function () {
    $admin = getCustMgmtAdmin();
    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/customers')
        ->assertSee('Customers');
})->group('admin', 'customers');

it('customer create page loads', function () {
    $admin = getCustMgmtAdmin();
    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/customers/new')
        ->assertSee('Customer');
})->group('admin', 'customers');

it('customer detail page loads', function () {
    $admin = getCustMgmtAdmin();
    $customer = Customer::factory()->create([
        'first_name' => 'BrowserTestCustomer',
        'last_name' => 'Detail',
    ]);
    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/customers/'.$customer->id)
        ->assertSee('BrowserTestCustomer');
})->group('admin', 'customers');

it('customer edit page loads', function () {
    $admin = getCustMgmtAdmin();
    $customer = Customer::factory()->create([
        'first_name' => 'EditTestCustomer',
        'last_name' => 'Browser',
    ]);
    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/customers/'.$customer->id.'/edit')
        ->assertSee('Edit Client');
})->group('admin', 'customers');

it('customer factory creates valid customer', function () {
    $customer = Customer::factory()->create();
    expect($customer->id)->toBeGreaterThan(0);
    expect($customer->first_name)->not->toBeEmpty();
    expect($customer->getTable())->toBe('customers');
})->group('admin', 'customers');
