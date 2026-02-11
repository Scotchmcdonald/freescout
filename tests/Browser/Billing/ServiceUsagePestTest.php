<?php

use App\Models\User;
use Modules\Crm\Models\Client;

it('usage logging and invoicing', function () {
    $admin = User::firstOrCreate(['email' => 'usage-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'Usage',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (!$admin->isAdmin()) { $admin->role = User::ROLE_ADMIN; $admin->save(); }

    $client = Client::factory()->create(['name' => 'Usage Test Client']);

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/billing/service-usage/create')
        ->assertSee('Service Entry');
})->group('billing', 'variable');
