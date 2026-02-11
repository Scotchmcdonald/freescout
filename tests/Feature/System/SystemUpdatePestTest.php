<?php

use App\Models\User;

test('update check requires admin', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER, 'type' => 2]);

    $this->actingAs($user)
        ->get(route('system.update'))
        ->assertForbidden();
});

test('update check allows admin', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->get(route('system.update'))
        ->assertOk();
});
