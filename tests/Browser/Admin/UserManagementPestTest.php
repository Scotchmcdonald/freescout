<?php

use App\Models\User;

function getUserMgmtAdmin(): User
{
    $admin = User::firstOrCreate(['email' => 'usermgmt-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'UserMgmtTest',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (! $admin->isAdmin()) {
        $admin->role = User::ROLE_ADMIN;
        $admin->save();
    }

    return $admin;
}

it('user list page loads', function () {
    $admin = getUserMgmtAdmin();
    browserLoginAdmin($this, $admin);

    $this->visit('/users')
        ->assertPathIs('/users');
})->group('admin', 'users');

it('user create page loads', function () {
    $admin = getUserMgmtAdmin();
    browserLoginAdmin($this, $admin);

    $this->visit('/users/create')
        ->assertPathIs('/users/create');
})->group('admin', 'users');

it('user detail page loads', function () {
    $admin = getUserMgmtAdmin();
    $testUser = User::factory()->create([
        'first_name' => 'TestDetail',
        'last_name' => 'UserBrowser',
    ]);
    browserLoginAdmin($this, $admin);

    $this->visit('/user/'.$testUser->id)
        ->assertSee('TestDetail');
})->group('admin', 'users');

it('user edit page loads', function () {
    $admin = getUserMgmtAdmin();
    $testUser = User::factory()->create([
        'first_name' => 'TestEdit',
        'last_name' => 'UserBrowser',
    ]);
    browserLoginAdmin($this, $admin);

    $this->visit('/user/'.$testUser->id.'/edit')
        ->assertPathIs('/user/'.$testUser->id.'/edit');
})->group('admin', 'users');

it('user permissions page loads', function () {
    $admin = getUserMgmtAdmin();
    $testUser = User::factory()->create([
        'first_name' => 'TestPerms',
        'last_name' => 'UserBrowser',
    ]);
    browserLoginAdmin($this, $admin);

    $this->visit('/user/'.$testUser->id.'/permissions')
        ->assertPathIs('/user/'.$testUser->id.'/permissions');
})->group('admin', 'users');
