<?php

declare(strict_types=1);

namespace Tests\Traits;

use App\Models\User;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\Company;

/**
 * Makes test actors with different roles for authorization testing.
 * Provides factory methods for creating users with specific permissions.
 */
trait MakesRoleActors
{
    /**
     * Create an admin user (ROLE_ADMIN = 2, TYPE_INTERNAL = 1).
     * Has all permissions via Gate::before() admin bypass.
     */
    protected function makeAdminUser(): User
    {
        return User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin-'.uniqid().'@test.local',
            'type' => User::TYPE_INTERNAL,
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    /**
     * Create a finance user (ROLE_FINANCE = 4, TYPE_INTERNAL = 1).
     * Attached to a company for company-scoped access.
     */
    protected function makeFinanceUser(Company $company): User
    {
        $user = User::factory()->create([
            'first_name' => 'Finance',
            'last_name' => 'User',
            'email' => 'finance-'.uniqid().'@test.local',
            'type' => User::TYPE_INTERNAL,
            'role' => User::ROLE_FINANCE,
            'status' => User::STATUS_ACTIVE,
        ]);

        $user->companies()->attach($company->id, [
            'status' => 'approved',
            'is_primary' => true,
            'is_approver' => false,
        ]);

        return $user;
    }

    /**
     * Create a technician user (ROLE_USER = 1, TYPE_INTERNAL = 1).
     * Attached to a company for company-scoped access via TechnicianScope.
     */
    protected function makeTechnicianUser(Company $company): User
    {
        $user = User::factory()->create([
            'first_name' => 'Technician',
            'last_name' => 'User',
            'email' => 'tech-'.uniqid().'@test.local',
            'type' => User::TYPE_INTERNAL,
            'role' => User::ROLE_USER,
            'status' => User::STATUS_ACTIVE,
        ]);

        $user->companies()->attach($company->id, [
            'status' => 'approved',
            'is_primary' => true,
            'is_approver' => false,
        ]);

        return $user;
    }

    /**
     * Create a technician user WITHOUT company access.
     * Will be filtered out by TechnicianScope on queries.
     */
    protected function makeTechnicianWithoutAccess(): User
    {
        return User::factory()->create([
            'first_name' => 'Isolated',
            'last_name' => 'Technician',
            'email' => 'isolated-tech-'.uniqid().'@test.local',
            'type' => User::TYPE_INTERNAL,
            'role' => User::ROLE_USER,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    /**
     * Create a client user (TYPE_CLIENT = 2).
     * Attached to a company and has client_id set.
     */
    protected function makeClientUser(Company $company, ?Client $client = null): User
    {
        $client = $client ?? Client::factory()->create(['company_id' => $company->id]);

        $user = User::factory()->create([
            'first_name' => 'Client',
            'last_name' => 'User',
            'email' => 'client-'.uniqid().'@test.local',
            'type' => User::TYPE_CLIENT,
            'role' => User::ROLE_USER,
            'status' => User::STATUS_ACTIVE,
        ]);

        $user->companies()->attach($company->id, [
            'status' => 'approved',
            'is_primary' => true,
            'is_approver' => false,
        ]);

        return $user;
    }

    /**
     * Create a guest user with minimal permissions (no roles, no company access).
     * For testing default/public access scenarios.
     */
    protected function makeGuestUser(): User
    {
        return User::factory()->create([
            'first_name' => 'Guest',
            'last_name' => 'User',
            'email' => 'guest-'.uniqid().'@test.local',
            'type' => User::TYPE_INTERNAL,
            'role' => User::ROLE_USER,
            'status' => User::STATUS_ACTIVE,
        ]);
    }
}
