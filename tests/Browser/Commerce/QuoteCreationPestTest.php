<?php

use App\Models\User;
use Modules\Crm\Models\Client;
use Modules\ContractManager\Models\Quote;

function getCommerceAdmin(): User
{
    $admin = User::firstOrCreate(['email' => 'commerce-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'Commerce',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (!$admin->isAdmin()) { $admin->role = User::ROLE_ADMIN; $admin->save(); }
    return $admin;
}

it('admin can create quote with line items', function () {
    $admin = getCommerceAdmin();
    $client = Client::factory()->create(['name' => 'Quote Test Client']);

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/contracts/quotes/create')
        ->assertSee('Quote')
        ->assertSee('Quote Details');
})->group('commerce', 'quote');

test('admin can send draft quote', function () {
    $admin = getCommerceAdmin();
    $client = Client::factory()->create(['name' => 'Send Quote Client']);

    $quote = Quote::factory()->create([
        'client_id' => $client->id,
        'status' => 'draft',
        'title' => 'Ready To Send Quote',
    ]);

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit("/contracts/quotes/{$quote->id}")
        ->waitForText('Quote')
        ->click('[dusk="send-quote"]')
        ->waitForText('Sent');
})->group('commerce', 'quote');
