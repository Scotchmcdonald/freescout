<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Crm\Models\Client;

uses(RefreshDatabase::class);

it('asset inventory page loads for admin', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $client = Client::factory()->create(['name' => 'Asset Client']);

    $this->actingAs($admin)
        ->get('/assets/inventory')
        ->assertOk()
        ->assertSee('Fleet Inventory');
});

it('asset inventory shows for multiple asset types', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    Client::factory()->create(['name' => 'Windows Client']);
    Client::factory()->create(['name' => 'Chromebook Client']);

    $this->actingAs($admin)
        ->get('/assets/inventory')
        ->assertOk()
        ->assertSee('Fleet Inventory');
});
