<?php

use App\Models\User;

function getSettingsAdmin(): User
{
    $admin = User::firstOrCreate(['email' => 'settings-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'SettingsTest',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (!$admin->isAdmin()) { $admin->role = User::ROLE_ADMIN; $admin->save(); }
    return $admin;
}

it('settings index page loads', function () {
    $admin = getSettingsAdmin();
    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/settings')
        ->assertSee('General Settings');
})->group('admin', 'settings');

it('general settings page loads', function () {
    $admin = getSettingsAdmin();
    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/settings/general')
        ->assertSee('General Settings');
})->group('admin', 'settings');

it('email settings page loads', function () {
    $admin = getSettingsAdmin();
    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/settings/email')
        ->assertSee('Email Settings');
})->group('admin', 'settings');

it('security settings page loads', function () {
    $admin = getSettingsAdmin();
    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/settings/security')
        ->assertSee('Settings');
})->group('admin', 'settings');

it('alert settings page loads', function () {
    $admin = getSettingsAdmin();
    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/settings/alerts')
        ->assertSee('Alert Settings');
})->group('admin', 'settings');
