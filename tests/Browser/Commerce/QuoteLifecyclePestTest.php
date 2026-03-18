<?php

use App\Models\User;
use Modules\ContractManager\Models\Quote;
use Modules\Crm\Models\Client;

it('full quote lifecycle rejection and revision', function () {
    $admin = User::firstOrCreate(['email' => 'quote-lifecycle-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'QuoteLC',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (! $admin->isAdmin()) {
        $admin->role = User::ROLE_ADMIN;
        $admin->save();
    }

    $client = Client::factory()->create(['name' => 'Lifecycle Client']);
    $quote = Quote::factory()->draft()->create([
        'client_id' => $client->id,
        'title' => 'Lifecycle Test Quote',
    ]);

    browserLoginAdmin($this, $admin);

    $this->visit("/contracts/quotes/{$quote->id}")
        ->assertSee($quote->title);
})->group('commerce', 'quote-lifecycle');
