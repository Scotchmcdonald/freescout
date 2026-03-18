<?php

use App\Models\User;
use Modules\Crm\Models\Client;

function getAssetAdmin(): User
{
    $admin = User::firstOrCreate(['email' => 'asset-lifecycle-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'Asset',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (! $admin->isAdmin()) {
        $admin->role = User::ROLE_ADMIN;
        $admin->save();
    }

    return $admin;
}

it('create windows asset', function () {
    $admin = getAssetAdmin();
    $client = Client::factory()->create(['name' => 'Windows Asset Client']);

    browserLoginAdmin($this, $admin);

    $this->visit('/assets/inventory')
        ->assertSee('Fleet Inventory');
})->group('service', 'asset');

it('create chromebook asset', function () {
    $admin = getAssetAdmin();
    $client = Client::factory()->create(['name' => 'Chromebook Asset Client']);

    browserLoginAdmin($this, $admin);

    $this->visit('/assets/inventory')
        ->assertSee('Fleet Inventory');
})->group('service', 'asset');
