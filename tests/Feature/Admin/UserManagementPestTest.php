<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('user list page loads for admin', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->get('/users')
        ->assertOk();
});

it('user create page loads for admin', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->get('/users/create')
        ->assertOk();
});

it('user detail page loads for admin', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $testUser = User::factory()->create([
        'first_name' => 'TestDetail',
        'last_name' => 'User',
    ]);

    $this->actingAs($admin)
        ->get('/user/'.$testUser->id)
        ->assertOk()
        ->assertSee('TestDetail');
});

it('user edit page loads for admin', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $testUser = User::factory()->create([
        'first_name' => 'TestEdit',
        'last_name' => 'User',
    ]);

    $this->actingAs($admin)
        ->get('/user/'.$testUser->id.'/edit')
        ->assertOk();
});
