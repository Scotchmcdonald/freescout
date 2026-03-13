<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

test('admin can clear cache', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    Cache::put('test_key', 'test_value', 60);
    expect(Cache::has('test_key'))->toBeTrue();

    $this->actingAs($admin)
        ->post(route('settings.cache.clear'))
        ->assertRedirect()
        ->assertSessionHas('success');
});

test('non-admin cannot clear cache', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER, 'type' => 2]);

    $this->actingAs($user)
        ->post(route('settings.cache.clear'))
        ->assertForbidden();
});

test('admin can run migrations', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    // We mock Artisan to avoid actual migration execution during test
    // We expect multiple calls: migrate and module:migrate
    Artisan::shouldReceive('call')
        ->with('migrate', Mockery::any())
        ->once();

    Artisan::shouldReceive('call')
        ->with('module:migrate', Mockery::any())
        ->once();

    $this->actingAs($admin)
        ->post(route('settings.migrate'))
        ->assertRedirect()
        ->assertSessionHas('success');
});

test('non-admin cannot run migrations', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER, 'type' => 2]);

    $this->actingAs($user)
        ->post(route('settings.migrate'))
        ->assertForbidden();
});
