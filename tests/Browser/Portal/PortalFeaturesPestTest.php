<?php

use Modules\Crm\Models\Client;
use Modules\Crm\Models\ClientUser;

it('portal dashboard shows client info', function () {
    $client = Client::factory()->create(['name' => 'Portal Features Client']);
    $clientUser = ClientUser::factory()->create([
        'client_id' => $client->id,
        'name' => 'Portal Dashboard User',
        'email' => 'portal-dash-' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    $this->visit('/portal/login')
        ->type('email', $clientUser->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/portal/dashboard')
        ->assertSee('Client Portal');
})->group('portal', 'features');

it('portal invoices page loads', function () {
    $client = Client::factory()->create(['name' => 'Portal Invoice Client']);
    $clientUser = ClientUser::factory()->create([
        'client_id' => $client->id,
        'name' => 'Portal Invoice User',
        'email' => 'portal-inv-' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    $this->visit('/portal/login')
        ->type('email', $clientUser->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/portal/invoices')
        ->assertSee('Invoice');
})->group('portal', 'features');

it('portal support page loads', function () {
    $client = Client::factory()->create(['name' => 'Portal Support Client']);
    $clientUser = ClientUser::factory()->create([
        'client_id' => $client->id,
        'name' => 'Portal Support User',
        'email' => 'portal-support-' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    $this->visit('/portal/login')
        ->type('email', $clientUser->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/portal/support')
        ->assertSee('Ticket');
})->group('portal', 'features');

it('portal approvals page loads', function () {
    $client = Client::factory()->create(['name' => 'Portal Approvals Client']);
    $clientUser = ClientUser::factory()->create([
        'client_id' => $client->id,
        'name' => 'Portal Approvals User',
        'email' => 'portal-approvals-' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    $this->visit('/portal/login')
        ->type('email', $clientUser->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/portal/approvals')
        ->assertSee('Approval');
})->group('portal', 'features');

it('portal billing account page loads', function () {
    $client = Client::factory()->create(['name' => 'Portal Billing Client']);
    $clientUser = ClientUser::factory()->create([
        'client_id' => $client->id,
        'name' => 'Portal Billing User',
        'email' => 'portal-billing-' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    $this->visit('/portal/login')
        ->type('email', $clientUser->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/portal/billing/account')
        ->assertSee('Account');
})->group('portal', 'features');
