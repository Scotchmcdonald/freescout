<?php

use App\Models\User;
use Modules\Crm\Models\Company;

function createPortalAccessUser(string $name, string $emailPrefix): User
{
    $company = Company::factory()->create(['name' => $name, 'is_active' => true]);
    $user = User::factory()->create([
        'type' => 2,
        'first_name' => $name,
        'last_name' => 'User',
        'email' => $emailPrefix . '-' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
        'status' => User::STATUS_ACTIVE,
        'email_verified_at' => now(),
    ]);
    $company->users()->attach($user->id, ['role_id' => 1, 'status' => 'approved', 'is_primary' => true]);
    return $user;
}

test('client can login to portal', function () {
    $user = createPortalAccessUser('Portal Client', 'portal');

    $this->visit('/portal/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->click('button[type="submit"]')
        ->assertPathIs('/portal/dashboard');
})->group('portal', 'auth');

test('client dashboard displays after login', function () {
    $user = createPortalAccessUser('Dashboard Client', 'dashboard');

    $this->visit('/portal/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/portal/dashboard')
        ->assertSee('Welcome');
})->group('portal', 'dashboard');
