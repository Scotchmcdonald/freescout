<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Crm\Models\Client;

uses(RefreshDatabase::class);

it('billing service usage page loads for admin', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $client = Client::factory()->create(['name' => 'Usage Test Client']);

    $this->actingAs($admin)
        ->get('/billing/service-usage/create')
        ->assertOk()
        ->assertSee('Service Entry');
});
