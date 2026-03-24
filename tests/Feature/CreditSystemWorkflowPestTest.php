<?php

declare(strict_types=1);

test('admin authentication works', function () {
    $admin = \App\Models\User::factory()->create([
        'role' => \App\Models\User::ROLE_ADMIN,
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertOk();
});
