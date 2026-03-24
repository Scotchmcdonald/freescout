<?php

declare(strict_types=1);

use App\Models\User;

test('webhooks index requires admin', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER, 'type' => 2]); // Not Admin

    $this->actingAs($user)
        ->get(route('webhooks'))
        ->assertForbidden();
});

test('webhooks index allows admin', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->get(route('webhooks'))
        ->assertOk();
});
