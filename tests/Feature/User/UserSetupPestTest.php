<?php

declare(strict_types=1);

use App\Models\User;

test('invited user can view setup page', function () {
    $user = User::factory()->create([
        'invite_hash' => 'valid_hash',
        'invite_state' => 2, // Sent
        'status' => 2, // Inactive
    ]);

    $response = $this->get(route('user_setup', ['hash' => 'valid_hash']));

    $response->assertOk();
});

test('invited user can complete setup', function () {
    $user = User::factory()->create([
        'invite_hash' => 'valid_hash',
        'invite_state' => 2, // Sent
        'status' => 2, // Inactive
    ]);

    $response = $this->post(route('user_setup.save', ['hash' => 'valid_hash']), [
        'email' => $user->email,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
        'timezone' => 'UTC',
        'time_format' => 24,
    ]);

    $response->assertRedirect(route('dashboard'));

    $user->refresh();
    expect($user->invite_hash)->toBeNull();
    expect($user->invite_state)->toBe(1); // Active/Accepted?
    // Legacy test expected 1.
});
