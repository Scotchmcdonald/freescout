<?php

use App\Models\User;

/**
 * Core Application Smoke Tests
 */

/**
 * Helper to get or create the admin user
 */
function getSmokeTestAdmin(): User
{
    // Use a strictly defined user for smoke testing to avoid password mismatch issues
    $email = 'smoke-test-admin@example.com';
    $user = User::where('email', $email)->first();
    
    if (!$user) {
        $user = User::factory()->create([
            'email' => $email,
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => User::ROLE_ADMIN
        ]);
    } else {
        // Ensure permissions/password
        if (!$user->isAdmin()) {
            $user->role = User::ROLE_ADMIN;
            $user->save();
        }
            // We can't easily reset password here without mocking Hash or force updating, 
            // but we assume if it exists with this specific email, it was created by us or seeder.
            // Best to force consistency in tests.
            $user->password = \Illuminate\Support\Facades\Hash::make('password');
            $user->save();
    }
    
    return $user;
}

test('application loads', function () {
    $this->visit('/')
        ->assertDontSee('500')
        ->assertDontSee('Error');
})->group('smoke');

test('admin can login', function () {
    $admin = getSmokeTestAdmin();

    $this->visit('/login')
        ->type('email', $admin->email) // 'email' selector?
        ->type('password', 'password')
        ->click('button[type="submit"]') // guess
        ->assertPathIs('/dashboard') // or similar
        ->assertDontSee('Invalid credentials');
})->group('smoke', 'auth');
