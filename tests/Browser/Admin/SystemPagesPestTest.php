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
        ->assertSee('System Dashboard');
})->group('admin', 'system');

it('system tools page loads', function () {
    $admin = getSystemPagesAdmin();
    browserLoginAdmin($this, $admin);

    $this->visit('/system/tools')
        ->assertSee('System Tools');
})->group('admin', 'system');

it('system logs page loads', function () {
    $admin = getSystemPagesAdmin();
    browserLoginAdmin($this, $admin);

    $this->visit('/system/logs')
        ->assertSee('System Logs');
})->group('admin', 'system');

it('failed jobs page loads', function () {
    $admin = getSystemPagesAdmin();
    browserLoginAdmin($this, $admin);

    $this->visit('/system/failed-jobs')
        ->assertSee('Failed Jobs');
})->group('admin', 'system');

it('system update page loads', function () {
    $admin = getSystemPagesAdmin();
    browserLoginAdmin($this, $admin);

    $this->visit('/system/update')
        ->assertSee('System');
})->group('admin', 'system');
