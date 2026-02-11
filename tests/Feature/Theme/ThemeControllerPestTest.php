<?php

use App\Models\Theme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

test('non admin cannot access themes page', function () {
    // Regular user should have type=0/default
    $regularUser = User::factory()->create(['role' => User::ROLE_USER, 'type' => 0]);

    $response = $this->actingAs($regularUser)->get(route('themes'));

    // Should be forbidden or redirected
    $isDenied = $response->isForbidden() || $response->isRedirect();
    expect($isDenied)->toBeTrue();
});

test('admin can access themes page', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $response = $this->actingAs($admin)->get(route('themes'));

    $response->assertStatus(200);
    $response->assertViewIs('themes.index');
});

test('guest cannot access themes page', function () {
    $response = $this->get(route('themes'));

    $response->assertRedirect(route('login'));
});

test('admin can update theme preference', function () {
    Theme::create(['name' => 'dark', 'title' => 'Dark', 'config' => []]);
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $response = $this->actingAs($admin)->post(route('themes.update'), [
        'theme' => 'dark',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    // Refresh user from database
    $admin->refresh();
    expect($admin->theme)->toBe('dark');
});

test('admin can set default theme', function () {
    Theme::create(['name' => 'default', 'title' => 'Default', 'config' => []]);
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'theme' => 'dark',
    ]);

    $response = $this->actingAs($admin)->post(route('themes.update'), [
        'theme' => 'default',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    // Refresh user from database - 'default' should be stored
    $admin->refresh();
    expect($admin->theme)->toBe('default');
});

test('theme page shows available themes', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $response = $this->actingAs($admin)->get(route('themes'));

    $response->assertStatus(200);
    $response->assertViewHas('themes');
    $response->assertViewHas('currentTheme');
});

test('user theme field is fillable', function () {
    $user = User::factory()->create(['theme' => 'dark']);

    expect($user->theme)->toBe('dark');
});

test('user can have null theme', function () {
    $user = User::factory()->create(['theme' => null]);
    expect($user->theme)->toBeNull();
});

test('user theme is persisted', function () {
    $user = User::factory()->create(['theme' => 'dark']);
    
    $user = User::find($user->id);
    expect($user->theme)->toBe('dark');
});
