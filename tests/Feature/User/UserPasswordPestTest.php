<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('user can view own password form', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('users.password', $user))
        ->assertOk()
        ->assertViewIs('users.password');
});

test('admin can view usage password form', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('users.password', $user))
        ->assertOk()
        ->assertViewIs('users.password');
});

test('user cannot view other users password form', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $otherUser = User::factory()->create(['role' => User::ROLE_USER]);

    $this->actingAs($user)
        ->get(route('users.password', $otherUser))
        ->assertForbidden();
});

test('user can update own password with valid current password', function () {
    $user = User::factory()->create([
        'role' => User::ROLE_USER,
        'password' => 'old_password_123',
    ]);

    $this->actingAs($user)
        ->from(route('users.password', $user))
        ->post(route('users.password.update', $user), [
            'current_password' => 'old_password_123',
            'password' => 'new_password_123',
            'password_confirmation' => 'new_password_123',
        ])
        ->assertRedirect(route('users.password', $user))
        ->assertSessionHas('success');

    expect(Hash::check('new_password_123', $user->fresh()->password))->toBeTrue();
});

test('user cannot update own password with invalid current password', function () {
    $user = User::factory()->create([
        'role' => User::ROLE_USER,
        'password' => 'old_password_123',
    ]);

    $this->actingAs($user)
        ->post(route('users.password.update', $user), [
            'current_password' => 'wrong_password',
            'password' => 'new_password_123',
            'password_confirmation' => 'new_password_123',
        ])
        ->assertSessionHasErrors('current_password');

    expect(Hash::check('old_password_123', $user->fresh()->password))->toBeTrue();
});

test('user cannot update own password with mismatching confirmation', function () {
    $user = User::factory()->create([
        'role' => User::ROLE_USER,
        'password' => 'old_password_123',
    ]);

    $this->actingAs($user)
        ->post(route('users.password.update', $user), [
            'current_password' => 'old_password_123',
            'password' => 'new_password_123',
            'password_confirmation' => 'different_password',
        ])
        ->assertSessionHasErrors('password');
});

test('admin can update other user password without current password', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $user = User::factory()->create([
        'role' => User::ROLE_USER,
        'password' => 'old_password_123',
    ]);

    $this->actingAs($admin)
        ->from(route('users.password', $user))
        ->post(route('users.password.update', $user), [
            // No current_password needed
            'password' => 'admin_set_password',
            'password_confirmation' => 'admin_set_password',
        ])
        ->assertRedirect(route('users.password', $user));

    expect(Hash::check('admin_set_password', $user->fresh()->password))->toBeTrue();
});

test('non admin cannot update other user password', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $otherUser = User::factory()->create(['role' => User::ROLE_USER]);

    $this->actingAs($user)
        ->post(route('users.password.update', $otherUser), [
            'password' => 'hacked_password',
            'password_confirmation' => 'hacked_password',
        ])
        ->assertForbidden();
});
