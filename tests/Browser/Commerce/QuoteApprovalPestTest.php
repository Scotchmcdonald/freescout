<?php

use App\Models\User;
use Modules\ContractManager\Models\Quote;
use Modules\Crm\Models\Client;

it('client can approve quote', function () {
    $client = Client::factory()->create(['name' => 'Approve Quote Client']);
    $quote = Quote::factory()->sent()->create([
        'client_id' => $client->id,
        'title' => 'Quote For Approval',
    ]);

    $admin = User::firstOrCreate(['email' => 'qa-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'Approval',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (! $admin->isAdmin()) {
        $admin->role = User::ROLE_ADMIN;
        $admin->save();
    }

    browserLoginAdmin($this, $admin);

    $this->visit("/contracts/quotes/{$quote->id}")
        ->assertSee('Quote')
        ->assertSee($quote->title);
})->group('commerce', 'approval');

it('client can reject quote', function () {
    $client = Client::factory()->create(['name' => 'Reject Quote Client']);
    $quote = Quote::factory()->sent()->create([
        'client_id' => $client->id,
        'title' => 'Quote For Rejection',
    ]);

    $admin = User::firstOrCreate(['email' => 'qa-admin2@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'Rejection',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (! $admin->isAdmin()) {
        $admin->role = User::ROLE_ADMIN;
        $admin->save();
    }

    browserLoginAdmin($this, $admin);

    $this->visit("/contracts/quotes/{$quote->id}")
        ->assertSee($quote->title);
})->group('commerce', 'approval');
