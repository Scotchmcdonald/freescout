<?php

use App\Models\User;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
});

test('modules index displays flash messages', function () {
    Cache::put('modules_flash', [
        'text' => 'Test flash message',
        'type' => 'success',
    ]);

    $response = $this->actingAs($this->admin)
        ->get(route('modules'));

    $response->assertOk();
    $response->assertViewHas('flashes');

    // Flash should be cleared after display
    expect(Cache::get('modules_flash'))->toBeNull();
});

test('modules index handles multiple flash messages', function () {
    Cache::put('modules_flash', [
        ['text' => 'Message 1', 'type' => 'success'],
        ['text' => 'Message 2', 'type' => 'warning'],
    ]);

    $response = $this->actingAs($this->admin)
        ->get(route('modules'));

    $response->assertOk();
    $response->assertViewHas('flashes', function ($flashes) {
        return count($flashes) >= 2;
    });
});

test('modules index shows remote modules', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('modules'));

    $response->assertOk();
    $response->assertViewHas('remoteModules');
});

test('modules list displays module metadata', function () {
    $response = $this->actingAs($this->admin)->get(route('modules'));

    $response->assertOk();
    $response->assertViewHas('modules', function ($modules) {
        // Verify each module has required metadata
        foreach ($modules as $module) {
            if (! isset($module['name']) || ! isset($module['alias']) ||
                ! isset($module['description']) || ! isset($module['enabled'])) {
                return false;
            }
        }

        return true;
    });
});
