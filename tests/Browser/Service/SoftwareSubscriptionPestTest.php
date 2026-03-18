<?php

use App\Models\User;

test('admin can browse software catalog', function () {
    $admin = User::firstOrCreate(['email' => 'sw-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'SW',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (! $admin->isAdmin()) {
        $admin->role = User::ROLE_ADMIN;
        $admin->save();
    }

    browserLoginAdmin($this, $admin);

    $this->visit('/software-subscriptions/catalog')
        ->assertSee('Software');
})->group('service', 'software');

it('admin can view client subscriptions', function () {
    $admin = User::firstOrCreate(['email' => 'sw-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'SW',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (! $admin->isAdmin()) {
        $admin->role = User::ROLE_ADMIN;
        $admin->save();
    }

    $client = \Modules\Crm\Models\Client::factory()->create(['name' => 'SW Subscriptions Client']);

    browserLoginAdmin($this, $admin);

    $this->visit("/modules/software-subscriptions/clients/{$client->id}")
        ->assertSee('Manage Assignments');
})->group('service', 'software');
