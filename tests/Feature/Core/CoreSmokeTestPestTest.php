<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('dashboard loads for admin after home redirect', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->get('/')
        ->assertRedirect('/dashboard');

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertOk();
});

it('admin dashboard accessible without errors', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertOk();
});
