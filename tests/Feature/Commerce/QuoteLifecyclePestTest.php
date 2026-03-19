<?php

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
