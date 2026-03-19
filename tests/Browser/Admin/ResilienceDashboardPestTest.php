<?php

use App\Models\User;

function getResilienceAdmin(): User
{
    $admin = User::firstOrCreate(['email' => 'resilience-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'ResilienceTest',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (! $admin->isAdmin()) {
        $admin->role = User::ROLE_ADMIN;
        $admin->save();
    }

    return $admin;
}

it('circuit breakers page loads', function () {
    $admin = getResilienceAdmin();
    browserLoginAdmin($this, $admin);

    $this->visit('/resilience')
        ->assertPathIs('/resilience');
})->group('admin', 'resilience');

it('resilience events page loads', function () {
    $admin = getResilienceAdmin();
    browserLoginAdmin($this, $admin);

    $this->visit('/resilience/events')
        ->assertPathIs('/resilience/events');
})->group('admin', 'resilience');

it('rate limits page loads', function () {
    $admin = getResilienceAdmin();
    browserLoginAdmin($this, $admin);

    $this->visit('/resilience')
        ->assertPresent('body');
})->group('admin', 'resilience');
