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
    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/users')
        ->assertSee('Users');
})->group('admin', 'users');

it('user create page loads', function () {
    $admin = getUserMgmtAdmin();
    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/users/create')
        ->assertSee('Create New User');
})->group('admin', 'users');

it('user detail page loads', function () {
    $admin = getUserMgmtAdmin();
    $testUser = User::factory()->create([
        'first_name' => 'TestDetail',
        'last_name' => 'UserBrowser',
    ]);
    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/user/'.$testUser->id)
        ->assertSee('TestDetail');
})->group('admin', 'users');

it('user edit page loads', function () {
    $admin = getUserMgmtAdmin();
    $testUser = User::factory()->create([
        'first_name' => 'TestEdit',
        'last_name' => 'UserBrowser',
    ]);
    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/user/'.$testUser->id.'/edit')
        ->assertSee('Edit User');
})->group('admin', 'users');

it('user permissions page loads', function () {
    $admin = getUserMgmtAdmin();
    $testUser = User::factory()->create([
        'first_name' => 'TestPerms',
        'last_name' => 'UserBrowser',
    ]);
    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/user/'.$testUser->id.'/permissions')
        ->assertSee('User Permissions');
})->group('admin', 'users');
