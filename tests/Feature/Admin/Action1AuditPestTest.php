<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('admin can access action1 audit page', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->get('/action1/audit')
        ->assertOk()
        ->assertSee('Activity Log');
});

it('action1 audit page shows empty state', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->get('/action1/audit')
        ->assertOk()
        ->assertSee('No activity recorded');
});
