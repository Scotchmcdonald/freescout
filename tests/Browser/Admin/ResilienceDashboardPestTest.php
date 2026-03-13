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
    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/resilience')
        ->assertSee('Resilience');
})->group('admin', 'resilience');

it('resilience events page loads', function () {
    $admin = getResilienceAdmin();
    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/resilience/events')
        ->assertSee('Event');
})->group('admin', 'resilience');

it('rate limits page loads', function () {
    $admin = getResilienceAdmin();
    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/resilience')
        ->assertSee('Rate');
})->group('admin', 'resilience');
