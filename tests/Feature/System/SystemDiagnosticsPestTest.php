<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
});

test('system diagnostics checks database connection', function () {
    $response = $this->actingAs($this->admin)->get(route('system.diagnostics'));

    $response->assertOk();
    $response->assertJsonStructure([
        'success',
        'checks' => [
            'database' => ['status', 'message'],
        ],
    ]);
    
    // Using array access helper from Pest or plain PHP
    expect($response->json('checks.database.status'))->toBe('ok');
});

test('system diagnostics checks storage writable', function () {
    $response = $this->actingAs($this->admin)->get(route('system.diagnostics'));

    $response->assertOk();
    expect($response->json('checks.storage.status'))->toBe('ok');
});

test('system diagnostics checks cache working', function () {
    $response = $this->actingAs($this->admin)->get(route('system.diagnostics'));

    $response->assertOk();
    expect($response->json('checks.cache.status'))->toBe('ok');
});

test('admin can get system info with correct structure', function () {
    $response = $this->actingAs($this->admin)->post(route('system.ajax'), [
        'action' => 'system_info',
    ]);

    $response->assertOk();
    $response->assertJsonStructure([
        'success',
        'info' => [
            'php_version',
            'laravel_version',
            'db_connection',
            'cache_driver',
            'queue_connection',
            'session_driver',
            'timezone',
            'locale',
        ],
    ]);
});
