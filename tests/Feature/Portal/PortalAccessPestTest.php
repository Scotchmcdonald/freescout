<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Crm\Models\Company;

uses(RefreshDatabase::class);

it('client portal dashboard displays for authenticated client', function () {
    // Create company and client user
    $company = Company::factory()->create(['name' => 'Portal Test Client', 'is_active' => true]);
    $user = User::factory()->create([
        'type' => 2,
        'first_name' => 'Portal',
        'last_name' => 'Client',
        'email' => 'portal-client@example.com',
        'password' => bcrypt('password'),
        'status' => User::STATUS_ACTIVE,
        'email_verified_at' => now(),
    ]);
    $company->users()->attach($user->id, ['role_id' => 1, 'status' => 'approved', 'is_primary' => true]);

    // Test that authenticated user can access portal dashboard
    $this->actingAs($user)
        ->get('/portal/dashboard')
        ->assertOk()
        ->assertSee('Welcome');
});

it('client can view portal invoices if authenticated', function () {
    $company = Company::factory()->create(['name' => 'Invoice Test Client', 'is_active' => true]);
    $user = User::factory()->create([
        'type' => 2,
        'first_name' => 'Invoice',
        'last_name' => 'Client',
        'email' => 'invoice-client@example.com',
        'password' => bcrypt('password'),
        'status' => User::STATUS_ACTIVE,
        'email_verified_at' => now(),
    ]);
    $company->users()->attach($user->id, ['role_id' => 1, 'status' => 'approved', 'is_primary' => true]);

    // Test that authenticated client can access portal invoices section
    $this->actingAs($user)
        ->get('/portal/invoices')
        ->assertOk();
});
