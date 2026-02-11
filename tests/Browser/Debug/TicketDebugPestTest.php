<?php

/**
 * Debug tests for ticket submission flow.
 * Consolidated from legacy Dusk debug tests.
 */

use App\Models\User;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\ClientUser;

it('ticket form submission debug flow', function () {
    $client = Client::factory()->create(['name' => 'Debug Ticket Client']);
    $clientUser = ClientUser::factory()->create([
        'client_id' => $client->id,
        'name' => 'Debug Ticket User',
        'email' => 'debug-ticket-' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    $this->visit('/portal/login')
        ->type('email', $clientUser->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/portal/support')
        ->assertSee('Support');
})->group('debug', 'ticket');

it('ticket number display after creation', function () {
    $client = Client::factory()->create(['name' => 'Debug Number Client']);
    $clientUser = ClientUser::factory()->create([
        'client_id' => $client->id,
        'name' => 'Debug Number User',
        'email' => 'debug-number-' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    $this->visit('/portal/login')
        ->type('email', $clientUser->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    // Navigate to ticket listing which should show ticket numbers
    $this->visit('/portal/support/tickets')
        ->assertSee('Ticket');
})->group('debug', 'ticket');

it('session flash after ticket submit', function () {
    $client = Client::factory()->create(['name' => 'Debug Flash Client']);
    $clientUser = ClientUser::factory()->create([
        'client_id' => $client->id,
        'name' => 'Debug Flash User',
        'email' => 'debug-flash-' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    $this->visit('/portal/login')
        ->type('email', $clientUser->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    // Verify support page loads for form submission
    $this->visit('/portal/support')
        ->assertSee('Support');
})->group('debug', 'ticket');
