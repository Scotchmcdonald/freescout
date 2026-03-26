<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ContractManager\Models\Quote;
use Modules\Crm\Models\Client;

uses(RefreshDatabase::class);

it('quote detail page loads for admin', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $client = Client::factory()->create(['name' => 'Quote Client']);
    $quote = Quote::factory()->draft()->create([
        'client_id' => $client->id,
        'title' => 'Test Quote',
    ]);

    $this->actingAs($admin)
        ->get("/contracts/quotes/{$quote->id}")
        ->assertOk()
        ->assertSee($quote->title);
});

it('redirects unauthenticated guest accessing quote detail', function () {
    $client = Client::factory()->create(['name' => 'Auth Guard Client']);
    $quote = Quote::factory()->draft()->create([
        'client_id' => $client->id,
        'title' => 'Authorization Boundary Quote',
    ]);

    // Authorization boundary: auth middleware must redirect guests to login
    $this->get("/contracts/quotes/{$quote->id}")
        ->assertRedirect();
});

it('redirects unauthenticated guest accessing quote list', function () {
    // Authorization boundary: the quote index is protected by the auth middleware
    $this->get('/contracts/quotes')
        ->assertRedirect();
});
