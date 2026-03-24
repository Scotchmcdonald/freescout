<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('permissions index requires admin', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    $this->actingAs($user)
        ->get(route('permissions'))
        ->assertForbidden();
});

test('permissions index allows admin', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->get(route('permissions'))
        ->assertOk();
});

test('permissions save requires admin', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    $this->actingAs($user)
        ->post(route('permissions.save'), [])
        ->assertForbidden();
});

test('permissions save allows admin', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->post(route('permissions.save'), [
            'permissions' => [],
        ])
        ->assertRedirect();

    $admin->refresh();
});
