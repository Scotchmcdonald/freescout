<?php

use App\Models\User;
use Modules\Crm\Models\Client;
use Modules\PIB\Models\BillingAdjustment;

function getBillAdjAdmin(): User
{
    $admin = User::firstOrCreate(['email' => 'billadj-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'BillAdjTest',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (! $admin->isAdmin()) {
        $admin->role = User::ROLE_ADMIN;
        $admin->save();
    }

    return $admin;
}

it('billing adjustments list loads', function () {
    $admin = getBillAdjAdmin();
    browserLoginAdmin($this, $admin);

    $this->visit('/billing/adjustments')
        ->assertPathIs('/billing/adjustments');
})->group('billing', 'adjustments');

it('billing adjustment create page loads', function () {
    $admin = getBillAdjAdmin();
    browserLoginAdmin($this, $admin);

    $this->visit('/billing/adjustments/create')
        ->assertPathIs('/billing/adjustments/create');
})->group('billing', 'adjustments');

it('billing adjustment detail loads', function () {
    $admin = getBillAdjAdmin();
    $client = Client::factory()->create(['name' => 'Adjustment Test Client']);
    $adjustment = BillingAdjustment::create([
        'client_id' => $client->id,
        'adjustment_type' => 'credit',
        'effective_date' => now()->toDateString(),
        'old_value' => '100.00',
        'new_value' => '80.00',
        'justification' => 'Test browser adjustment',
        'status' => 'pending',
        'created_by' => $admin->id,
    ]);

    browserLoginAdmin($this, $admin);

    $this->visit('/billing/adjustments/'.$adjustment->id)
        ->assertPresent('body');
})->group('billing', 'adjustments');

it('billing adjustment model works', function () {
    $admin = getBillAdjAdmin();
    $client = Client::factory()->create(['name' => 'Model Test Client']);
    $adjustment = BillingAdjustment::create([
        'client_id' => $client->id,
        'adjustment_type' => 'discount',
        'effective_date' => now()->toDateString(),
        'old_value' => '200.00',
        'new_value' => '150.00',
        'justification' => 'Model test adjustment',
        'status' => 'pending',
        'created_by' => $admin->id,
    ]);

    expect($adjustment->id)->toBeGreaterThan(0);
    expect($adjustment->adjustment_type)->toBe('discount');
    expect($adjustment->status)->toBe('pending');
    expect($adjustment->client_id)->toBe($client->id);
})->group('billing', 'adjustments');
