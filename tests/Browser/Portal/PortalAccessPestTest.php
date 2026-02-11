<?php

use Modules\Crm\Models\Client;
use Modules\Crm\Models\ClientUser;

test('client can login to portal', function () {
    $client = Client::factory()->create(['name' => 'Portal Client']);
    $clientUser = ClientUser::factory()->create([
        'client_id' => $client->id,
        'name' => 'Portal User',
        'email' => 'portal-' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    $this->visit('/portal/login')
        ->type('email', $clientUser->email)
        ->type('password', 'password')
        ->click('button[type="submit"]')
        ->assertPathIs('/portal/dashboard');
})->group('portal', 'auth');

test('client dashboard displays after login', function () {
    $client = Client::factory()->create(['name' => 'Dashboard Client']);
    $clientUser = ClientUser::factory()->create([
        'client_id' => $client->id,
        'name' => 'Dashboard User',
        'email' => 'dashboard-' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    $this->visit('/portal/login')
        ->type('email', $clientUser->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/portal/dashboard')
        ->assertSee('Welcome');
})->group('portal', 'dashboard');
