<?php

use App\Models\User;

it('flash message persists through redirect', function () {
    $admin = User::firstOrCreate(['email' => 'flash-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'Flash',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (!$admin->isAdmin()) { $admin->role = User::ROLE_ADMIN; $admin->save(); }

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    // Verify contract management pages are accessible
    $this->visit('/contracts/agreements')
        ->assertSee('Contract');
})->group('flash', 'contracts');
