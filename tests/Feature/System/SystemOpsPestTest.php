<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

test('system can clear cache', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $response = $this->actingAs($admin)->post(route('settings.cache.clear'));

    $response->assertRedirect();
    $response->assertSessionHas('success');
});

test('system can run migrations', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    Artisan::shouldReceive('call')
        ->with('migrate', ['--force' => true])
        ->once()
        ->andReturn(0);

    Artisan::shouldReceive('call')
        ->with('module:migrate', ['--force' => true])
        ->once()
        ->andReturn(0);

    $response = $this->actingAs($admin)->post(route('settings.migrate'));

    $response->assertRedirect();
});

test('system shows logs for admin', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $response = $this->actingAs($admin)->get(route('system.logs'));

    $response->assertOk();
    $response->assertViewIs('system.logs');
});
