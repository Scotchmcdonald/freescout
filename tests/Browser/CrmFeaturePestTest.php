<?php

use App\Models\User;
use Modules\Crm\Models\Client;

function getCrmAdmin(): User
{
    $admin = User::firstOrCreate(['email' => 'crm-test-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'CRM',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (!$admin->isAdmin()) { $admin->role = User::ROLE_ADMIN; $admin->save(); }
    return $admin;
}

it('can create client', function () {
    $admin = getCrmAdmin();
    $clientName = 'Test Client ' . uniqid();
    $clientEmail = 'client-' . uniqid() . '@example.com';

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/crm/clients/create')
        ->assertSee('Create Client')
        ->type('#name', $clientName)
        ->type('#email', $clientEmail)
        ->click('[dusk="save-client-btn"]')
        ->waitForText($clientName)
        ->assertSee($clientName);
})->group('crm', 'smoke');

it('can add contacts to client', function () {
    $admin = getCrmAdmin();
    $client = Client::factory()->create(['name' => 'Contact Test Client']);

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit("/clients/{$client->id}")
        ->assertSee($client->name)
        ->click('[dusk="contacts-tab"]')
        ->waitForText('Contacts');
})->group('crm');

test('client 360 view displays', function () {
    $admin = getCrmAdmin();
    $client = Client::factory()->create(['name' => '360 View Client']);

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit("/clients/{$client->id}")
        ->assertSee($client->name)
        ->assertNotSee('404')
        ->assertNotSee('undefined');
})->group('crm', 'integration');
