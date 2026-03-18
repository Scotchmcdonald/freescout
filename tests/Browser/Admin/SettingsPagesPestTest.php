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
    if (! $admin->isAdmin()) {
        $admin->role = User::ROLE_ADMIN;
        $admin->save();
    }

    return $admin;
}

it('settings index page loads', function () {
    $admin = getSettingsAdmin();
    browserLoginAdmin($this, $admin);

    $this->visit('/settings')
        ->assertPathIs('/settings');
})->group('admin', 'settings');

it('general settings page loads', function () {
    $admin = getSettingsAdmin();
    browserLoginAdmin($this, $admin);

    $this->visit('/settings/general')
        ->assertPathIs('/settings/general');
})->group('admin', 'settings');

it('email settings page loads', function () {
    $admin = getSettingsAdmin();
    browserLoginAdmin($this, $admin);

    $this->visit('/settings/email')
        ->assertPathIs('/settings/email');
})->group('admin', 'settings');

it('security settings page loads', function () {
    $admin = getSettingsAdmin();
    browserLoginAdmin($this, $admin);

    $this->visit('/settings/security')
        ->assertPathIs('/settings/security');
})->group('admin', 'settings');

it('alert settings page loads', function () {
    $admin = getSettingsAdmin();
    browserLoginAdmin($this, $admin);

    $this->visit('/settings/alerts')
        ->assertPathIs('/settings/alerts');
})->group('admin', 'settings');
