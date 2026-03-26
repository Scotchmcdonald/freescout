<?php

declare(strict_types=1);

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

it('asset inventory returns 403 for non-admin users', function () {
    // Authorization boundary: asset inventory is admin-only;
    // external (type=0) users without admin privileges must be denied with 403.
    $externalUser = User::factory()->create(['role' => User::ROLE_USER, 'type' => 0]);

    $this->actingAs($externalUser)
        ->get('/assets/inventory')
        ->assertForbidden(); // 403 — authorization boundary enforced by admin middleware
});
