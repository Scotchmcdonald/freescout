<?php

use App\Models\User;

function getSystemPagesAdmin(): User
{
    $admin = User::firstOrCreate(['email' => 'system-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'SystemTest',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (! $admin->isAdmin()) {
        $admin->role = User::ROLE_ADMIN;
        $admin->save();
    }

    return $admin;
}

it('system dashboard loads', function () {
    $admin = getSystemPagesAdmin();
    browserLoginAdmin($this, $admin);

    $this->visit('/system')
        ->assertPathIs('/system');
})->group('admin', 'system');

it('system tools page loads', function () {
    $admin = getSystemPagesAdmin();
    browserLoginAdmin($this, $admin);

    $this->visit('/system/tools')
        ->assertPathIs('/system/tools');
})->group('admin', 'system');

it('system logs page loads', function () {
    $admin = getSystemPagesAdmin();
    browserLoginAdmin($this, $admin);

    $this->visit('/system/logs')
        ->assertPathIs('/system/logs');
})->group('admin', 'system');

it('failed jobs page loads', function () {
    $admin = getSystemPagesAdmin();
    browserLoginAdmin($this, $admin);

    $this->visit('/system/failed-jobs')
        ->assertPathIs('/system/failed-jobs');
})->group('admin', 'system');

it('system update page loads', function () {
    $admin = getSystemPagesAdmin();
    browserLoginAdmin($this, $admin);

    $this->visit('/system/update')
        ->assertPathIs('/system/update');
})->group('admin', 'system');
