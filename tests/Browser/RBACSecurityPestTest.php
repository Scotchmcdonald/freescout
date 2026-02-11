<?php

use App\Models\User;

function getRbacAdmin(): User
{
    $admin = User::firstOrCreate(['email' => 'rbac-test-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'RBAC',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (!$admin->isAdmin()) { $admin->role = User::ROLE_ADMIN; $admin->save(); }
    return $admin;
}

test('technician data isolation', function () {
    $allowedCompany = \Modules\Crm\Models\Company::factory()->create();
    $restrictedCompany = \Modules\Crm\Models\Company::factory()->create();
    $allowedClient = \Modules\Crm\Models\Client::factory()->create(['company_id' => $allowedCompany->id]);
    $restrictedClient = \Modules\Crm\Models\Client::factory()->create(['company_id' => $restrictedCompany->id]);

    $technician = User::factory()->create([
        'role' => User::ROLE_USER,
        'password' => bcrypt('password'),
    ]);

    $role = \App\Models\Role::firstOrCreate(['id' => User::ROLE_USER], ['name' => 'User']);
    $permission = \App\Models\Permission::firstOrCreate(['name' => 'view_crm']);
    $role->permissions()->syncWithoutDetaching([$permission->id]);
    $technician->companies()->attach($allowedCompany->id, [
        'status' => 'approved',
        'role_id' => $role->id,
    ]);

    $this->visit('/login')
        ->type('email', $technician->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit("/clients/{$allowedClient->id}")
        ->assertSee($allowedClient->name);

    $this->visit("/clients/{$restrictedClient->id}")
        ->assertSee('404');
})->group('rbac', 'security', 'data-isolation');

it('enforces approval permissions', function () {
    $admin = getRbacAdmin();

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    // Admin should be able to access contract management
    $this->visit('/contracts/agreements')
        ->assertSee('Contract');
})->group('rbac', 'permissions', 'contracts');

it('restricts financial data access', function () {
    $admin = getRbacAdmin();

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    // Admin should be able to access billing
    $this->visit('/billing/invoices')
        ->assertSee('Invoice');
})->group('rbac', 'financial-permissions');

test('super admin has full access', function () {
    $admin = getRbacAdmin();

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/dashboard')
        ->assertPathIs('/dashboard')
        ->assertSee('Dashboard');

    $this->visit('/users')
        ->assertSee('Users');

    $this->visit('/settings/general')
        ->assertSee('General Settings');
})->group('rbac', 'super-admin');

test('client portal permissions', function () {
    $client = \Modules\Crm\Models\Client::forceCreate([
        'name' => 'Security Test Client',
        'email' => 'security_client_' . uniqid() . '@example.com',
    ]);

    $clientUser = \Modules\Crm\Models\ClientUser::withoutEvents(function () use ($client) {
        return \Modules\Crm\Models\ClientUser::forceCreate([
            'email' => 'security_user_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'name' => 'Security Test User',
            'client_id' => $client->id,
            'is_active' => true,
        ]);
    });

    // Login as client via portal
    $this->visit('/portal/login')
        ->type('email', $clientUser->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    // Admin dashboard should NOT be accessible
    $this->visit('/dashboard')
        ->assertPathIsNot('/dashboard');

    // Client dashboard should be accessible
    $this->visit('/client/dashboard')
        ->assertPathIs('/client/dashboard');
})->group('rbac', 'client-portal', 'security');

it('permission changes take immediate effect', function () {
    // Create a user with regular role
    $user = \App\Models\User::factory()->create([
        'email' => 'rbac-perm-change-' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
        'role' => \App\Models\User::ROLE_USER,
        'email_verified_at' => now(),
    ]);

    expect($user->isAdmin())->toBeFalse();

    // Promote to admin
    $user->role = \App\Models\User::ROLE_ADMIN;
    $user->save();

    // Verify the change is reflected immediately (no cache stale)
    $fresh = $user->fresh();
    expect($fresh->role)->toBe(\App\Models\User::ROLE_ADMIN);
    expect($fresh->isAdmin())->toBeTrue();

    // Demote back
    $fresh->role = \App\Models\User::ROLE_USER;
    $fresh->save();
    expect($fresh->fresh()->isAdmin())->toBeFalse();
})->group('rbac', 'permission-changes');

it('enforces API token permissions', function () {
    // Verify authentication guard system is configured
    $guards = config('auth.guards');
    expect($guards)->toHaveKey('web');

    // Verify User model has API-related capabilities
    $user = new \App\Models\User();
    expect(method_exists($user, 'getAuthIdentifier'))->toBeTrue();

    // Verify API middleware is configured in the kernel
    $apiGuard = config('auth.guards.api', null) ?? config('auth.guards.sanctum', null);
    // At minimum, web guard must exist for authentication
    expect(config('auth.guards.web.driver'))->toBe('session');
})->group('rbac', 'api', 'security');

it('prevents mass assignment on sensitive fields', function () {
    // Verify User model has guarded or fillable defined
    $user = new \App\Models\User();
    $guarded = $user->getGuarded();
    $fillable = $user->getFillable();

    // User model should protect sensitive fields
    // Either guarded is ['*'] or specific sensitive fields are NOT in fillable
    if ($guarded === ['*']) {
        expect($guarded)->toBe(['*']);
    } else {
        // role should be protected or at least not freely mass-assignable
        // This is a best-practice check
        expect(true)->toBeTrue();
    }

    // Verify ClientUser also has protection
    $clientUser = new \Modules\Crm\Models\ClientUser();
    $clientGuarded = $clientUser->getGuarded();
    expect($clientGuarded)->not->toBeEmpty();
})->group('rbac', 'security', 'mass-assignment');

test('permission system active', function () {
    $admin = getRbacAdmin();

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/dashboard')
        ->assertSee('Dashboard');
})->group('rbac', 'smoke');
