<?php

use App\Models\User;
use Modules\Crm\Models\Company;

function createPortalFeatureUser(string $name, string $emailPrefix): User
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

it('portal dashboard shows client info', function () {
    $user = createPortalFeatureUser('Portal Features Client', 'portal-dash');

    $this->visit('/portal/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/portal/dashboard')
        ->assertSee('Client Portal');
})->group('portal', 'features');

it('portal invoices page loads', function () {
    $user = createPortalFeatureUser('Portal Invoice Client', 'portal-inv');

    $this->visit('/portal/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/portal/invoices')
        ->assertSee('Invoice');
})->group('portal', 'features');

it('portal support page loads', function () {
    $user = createPortalFeatureUser('Portal Support Client', 'portal-support');

    $this->visit('/portal/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/portal/support')
        ->assertSee('Ticket');
})->group('portal', 'features');

it('portal approvals page loads', function () {
    $user = createPortalFeatureUser('Portal Approvals Client', 'portal-approvals');

    $this->visit('/portal/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/portal/approvals')
        ->assertSee('Approval');
})->group('portal', 'features');

it('portal billing account page loads', function () {
    $user = createPortalFeatureUser('Portal Billing Client', 'portal-billing');

    $this->visit('/portal/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/portal/billing/account')
        ->assertSee('Account');
})->group('portal', 'features');
