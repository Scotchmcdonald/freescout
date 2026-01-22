<?php

/**
 * RBAC (Role-Based Access Control) Tests
 * 
 * Validates role-based access control across all modules.
 * Ensures security through proper permission enforcement.
 * 
 * PRIORITY: ⭐⭐⭐ (Medium - Security Compliance)
 * 
 * RUNNING TESTS:
 * php artisan dusk tests/Browser/RBACSecurityTest.php
 * php artisan dusk --group=rbac
 * php artisan dusk --group=security
 */

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\DuskTestCase;

class RBACSecurityTest extends DuskTestCase
{
    protected function getAdminUser(): User
    {
        return User::where('email', 'admin@example.com')->orWhere('role', User::ROLE_ADMIN)->firstOrFail();
    }

    #[Group('rbac')]
    #[Group('security')]
    #[Group('data-isolation')]
    public function test_technician_data_isolation(): void
    {
        // 1. Create Companies
        $allowedCompany = \Modules\Crm\Models\Company::factory()->create();
        $restrictedCompany = \Modules\Crm\Models\Company::factory()->create();

        // 2. Create Clients assigned to companies
        $allowedClient = \Modules\Crm\Models\Client::factory()->create(['company_id' => $allowedCompany->id]);
        $restrictedClient = \Modules\Crm\Models\Client::factory()->create(['company_id' => $restrictedCompany->id]);

        // 3. Create Technician User
        $technician = User::factory()->create(['role' => User::ROLE_USER]);

        // Grant View CRM permission (Assumption: Techs generally can view CRM if assigned)
        $role = \App\Models\Role::firstOrCreate(['id' => User::ROLE_USER], ['name' => 'User']);
        $permission = \App\Models\Permission::firstOrCreate(['name' => 'view_crm']);
        $role->permissions()->syncWithoutDetaching([$permission->id]);

        // 4. Assign Technician to Allowed Company
        $technician->companies()->attach($allowedCompany->id, [
            'status' => 'approved',
            'role_id' => $role->id
        ]);

        $this->browse(function (Browser $browser) use ($technician, $allowedClient, $restrictedClient) {
            $browser->loginAs($technician)
                // Try to view allowed client
                ->visit("/admin/clients/{$allowedClient->id}")
                ->assertSee($allowedClient->name)
                
                // Try to view restricted client
                ->visit("/admin/clients/{$restrictedClient->id}")
                ->assertSee('404');
        });
    }

    #[Group('rbac')]
    #[Group('permissions')]
    #[Group('contracts')]
    public function test_approval_permission_enforcement(): void
    {
        $this->markTestIncomplete(
            'Requires permission-based UI element hiding and API enforcement.'
        );
    }

    #[Group('rbac')]
    #[Group('financial-permissions')]
    public function test_financial_data_restriction(): void
    {
        $this->markTestIncomplete(
            'Requires role-based access to financial modules.'
        );
    }

    #[Group('rbac')]
    #[Group('super-admin')]
    public function test_super_admin_full_access(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->getAdminUser())
                ->visit('/dashboard')
                ->assertPathIs('/dashboard')
                ->assertSee('Dashboard')
                // Verify access to key admin areas
                ->visit('/users')
                ->assertSee('Users')
                ->visit('/settings/general')
                ->assertSee('General Settings');
        });
    }

    #[Group('rbac')]
    #[Group('client-portal')]
    #[Group('security')]
    public function test_client_portal_permissions(): void
    {
        // Create a temporary client for the user
        $client = \Modules\Crm\Models\Client::forceCreate([
            'name' => 'Security Test Client',
            'email' => 'security_test_client_company_' . time() . '@example.com',
        ]);

        // Create a temporary client user for security testing
        $clientUser = \Modules\Crm\Models\ClientUser::withoutEvents(function () use ($client) {
             return \Modules\Crm\Models\ClientUser::forceCreate([
                'email' => 'security_test_client_' . time() . '@example.com',
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'name' => 'Security Test User',
                'client_id' => $client->id,
                'is_active' => true,
            ]);
        });
        
        try {
            $this->browse(function (Browser $browser) use ($clientUser) {
                // Ensure no previous admin session persists
                $browser->driver->manage()->deleteAllCookies();
                
                // Login as client
                $browser->loginAs($clientUser, 'client')
                    // Attempt to visit Admin Dashboard
                    ->visit('/dashboard')
                    // Should be redirected to login (if not authenticated as web)
                    // Note: If redirected to /login, the path will likely be /login
                    ->assertPathIsNot('/dashboard');
                
                // Verify legitimate access
                $browser->visit('/client/dashboard')
                    ->assertPathIs('/client/dashboard');
            });
        } finally {
            $clientUser->delete();
            $client->delete();
        }
    }

    #[Group('rbac')]
    #[Group('permission-changes')]
    public function test_permission_changes_immediate_effect(): void
    {
        $this->markTestIncomplete(
            'Requires dynamic permission reloading without logout.'
        );
    }

    #[Group('rbac')]
    #[Group('api')]
    #[Group('security')]
    public function test_api_token_permission_enforcement(): void
    {
        $this->markTestIncomplete(
            'Requires API token scope validation.'
        );
    }

    #[Group('rbac')]
    #[Group('security')]
    #[Group('mass-assignment')]
    public function test_mass_assignment_protection(): void
    {
        $this->markTestIncomplete(
            'Requires mass assignment protection for sensitive fields.'
        );
    }

    #[Group('rbac')]
    #[Group('smoke')]
    public function test_permission_system_active(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->getAdminUser())
                ->visit('/dashboard')
                ->assertSee('Dashboard');
        });
    }
}
