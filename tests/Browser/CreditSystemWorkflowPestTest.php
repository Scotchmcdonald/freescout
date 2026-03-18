<?php

use App\Models\User;

test('credit system accessible', function () {
    $admin = User::firstOrCreate(['email' => 'credit-admin-browser@example.com'], [
        'role' => User::ROLE_ADMIN,
        'password' => bcrypt('password'),
        'first_name' => 'Credit',
        'last_name' => 'AdminBrowser',
        'email_verified_at' => now(),
    ]);

    browserLoginAdmin($this, $admin);
})->group('credits', 'smoke');
