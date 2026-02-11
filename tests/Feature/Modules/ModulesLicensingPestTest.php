<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;


beforeEach(function () {
    $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $this->user = User::factory()->create(['role' => User::ROLE_USER]);

    // Prevent real HTTP requests
    Http::preventStrayRequests();
});

test('admin can activate license', function () {
    $this->actingAs($this->admin);

    Http::fake([
        '*' => Http::response([
            'success' => true,
            'license' => 'valid',
        ], 200),
    ]);

    $response = $this->postJson(route('modules.ajax'), [
        'action' => 'activate_license',
        'alias' => 'test-module',
        'license' => 'test-license-key',
    ]);

    $response->assertOk();
    expect($response->json('success'))->toBeTrue();
});

test('non admin cannot activate license', function () {
    $this->actingAs($this->user);

    $response = $this->postJson(route('modules.ajax'), [
        'action' => 'activate_license',
        'alias' => 'test-module',
        'license' => 'test-license-key',
    ]);

    $response->assertForbidden();
});

test('activate license handles api failure', function () {
    $this->actingAs($this->admin);

    Http::fake([
        '*' => Http::response([
            'success' => false,
            'error' => 'invalid_license',
        ], 200),
    ]);

    $response = $this->postJson(route('modules.ajax'), [
        'action' => 'activate_license',
        'alias' => 'test-module',
        'license' => 'invalid-key',
    ]);

    $response->assertOk();
    // Use null coalescing to handle potential different error structure
    $success = $response->json('success') ?? true;
    expect($success)->toBeFalse();
});

// Since we can't easily mock Option::get for "deactivate" logic without partial mocks in Pest 
// (unless we seed DB options which might be static cached), 
// we will trust the existing controller logic or add DB seeding if needed.
// The legacy test used: \App\Models\Option::set('module_licenses', json_encode(['test-module' => 'test-license']));

test('admin can deactivate license', function () {
    $this->actingAs($this->admin);
    
    // Seed the license
    \App\Models\Option::set('module_licenses', json_encode(['test-module' => 'test-license']));

    Http::fake([
        '*' => Http::response([
            'success' => true,
        ], 200),
    ]);

    $response = $this->postJson(route('modules.ajax'), [
        'action' => 'deactivate_license',
        'alias' => 'test-module',
    ]);

    $response->assertOk();
    expect($response->json('success'))->toBeTrue();
});
