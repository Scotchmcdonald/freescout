<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Crm\Models\Client;

uses(RefreshDatabase::class);

it('software catalog page loads for admin', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->get('/software-subscriptions/catalog')
        ->assertOk()
        ->assertSee('Software');
});

it('client subscriptions page loads for admin', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $client = Client::factory()->create(['name' => 'SW Client']);

    $this->actingAs($admin)
        ->get("/modules/software-subscriptions/clients/{$client->id}")
        ->assertOk()
        ->assertSee('Manage Assignments');
});
