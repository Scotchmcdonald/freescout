<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Crm\Models\Company;

function createPortalFeatureUser(string $name, string $emailPrefix): User
{
    $company = Company::factory()->create(['name' => $name, 'is_active' => true]);
    $user = User::factory()->create([
        'type' => 2,
        'first_name' => $name,
        'last_name' => 'User',
        'email' => $emailPrefix.'-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'status' => User::STATUS_ACTIVE,
        'email_verified_at' => now(),
    ]);
    $company->users()->attach($user->id, ['role_id' => 1, 'status' => 'approved', 'is_primary' => true]);

    return $user;
}

it('portal dashboard shows client info', function () {
    $user = createPortalFeatureUser('Portal Features Client', 'portal-dash');

    browserLoginPortal($this, $user);

    $this->visit('/portal/dashboard')
        ->assertPathIs('/portal/dashboard');
})->group('portal', 'features');

it('portal invoices page loads', function () {
    $user = createPortalFeatureUser('Portal Invoice Client', 'portal-inv');

    browserLoginPortal($this, $user);

    $this->visit('/portal/invoices')
        ->assertPathIs('/portal/invoices');
})->group('portal', 'features');

it('portal support page loads', function () {
    $user = createPortalFeatureUser('Portal Support Client', 'portal-support');

    browserLoginPortal($this, $user);

    $this->visit('/portal/support')
        ->assertPathIs('/portal/support');
})->group('portal', 'features');

it('portal approvals page loads', function () {
    $user = createPortalFeatureUser('Portal Approvals Client', 'portal-approvals');

    browserLoginPortal($this, $user);

    $this->visit('/portal/approvals')
        ->assertPathIs('/portal/approvals');
})->group('portal', 'features');

it('portal billing account page loads', function () {
    $user = createPortalFeatureUser('Portal Billing Client', 'portal-billing');

    browserLoginPortal($this, $user);

    $this->visit('/portal/billing/account')
        ->assertPathIs('/portal/billing/account');
})->group('portal', 'features');
