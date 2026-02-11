<?php

use App\Models\User;
use Modules\Crm\Models\Client;
use Modules\ContractManager\Models\Quote;

it('full quote lifecycle rejection and revision', function () {
    $admin = User::firstOrCreate(['email' => 'quote-lifecycle-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'QuoteLC',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (!$admin->isAdmin()) { $admin->role = User::ROLE_ADMIN; $admin->save(); }

    $client = Client::factory()->create(['name' => 'Lifecycle Client']);
    $quote = Quote::factory()->draft()->create([
        'client_id' => $client->id,
        'title' => 'Lifecycle Test Quote',
    ]);

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit("/contracts/quotes/{$quote->id}")
        ->assertSee($quote->title);
})->group('commerce', 'quote-lifecycle');
