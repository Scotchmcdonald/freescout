<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can view integrations settings page', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->get(route('settings.integrations'))
        ->assertOk()
        ->assertViewIs('settings.integrations')
        ->assertSee('Integrations');
});

test('admin can view google workspace integration tab', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->get(route('settings.integrations', ['tab' => 'googleadmin']))
        ->assertOk()
        ->assertSee('Google Workspace Integration')
        ->assertSee('Enable Google Workspace synchronization');
});

test('admin can view action1 integration tab', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->get(route('settings.integrations', ['tab' => 'action1']))
        ->assertOk()
        ->assertSee('Action1 RMM Integration')
        ->assertSee('Enable Action1 RMM synchronization');
});

test('non-admin cannot access integrations settings', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER, 'type' => 2]);

    $this->actingAs($user)
        ->get(route('settings.integrations'))
        ->assertForbidden();
});

test('settings sidebar shows integrations section when modules are installed', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $response = $this->actingAs($admin)
        ->get(route('settings.integrations'));
    
    $response->assertOk();
    $sections = $response->viewData('sections');
    
    expect($sections)->toHaveKey('integrations');
    expect($sections['integrations']['title'])->toBe('Integrations');
    expect($sections['integrations']['route'])->toBe('settings.integrations');
});
