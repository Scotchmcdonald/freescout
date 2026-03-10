<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Role;
use App\Models\User;

/**
 * Migrate legacy users.role integer values to the new role_user RBAC pivot table.
 *
 * Legacy mapping:
 *   users.role = 1 (Agent)    → "MSP Technician" RBAC role
 *   users.role = 2 (Admin)    → "MSP Admin" RBAC role
 *   users.role = 3 (Reporter) → "MSP Reporter" RBAC role
 *   users.role = 4 (Finance)  → "MSP Finance" RBAC role
 *
 * This migration is idempotent — it will not create duplicate pivot rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Ensure the RBAC roles exist (safe to run even if RbacSeeder hasn't run yet)
        $roleMap = [
            'MSP Admin'      => ['label' => 'MSP Administrator', 'is_super_admin' => true, 'scope' => 'internal'],
            'MSP Finance'    => ['label' => 'MSP Finance',       'is_super_admin' => false, 'scope' => 'internal'],
            'MSP Technician' => ['label' => 'MSP Technician',    'is_super_admin' => false, 'scope' => 'internal'],
            'MSP Reporter'   => ['label' => 'MSP Reporter',      'is_super_admin' => false, 'scope' => 'internal'],
        ];

        foreach ($roleMap as $name => $attrs) {
            Role::firstOrCreate(['name' => $name], $attrs);
        }

        // Map legacy integer values to RBAC role names
        $legacyToRbac = [
            1 => 'MSP Technician',  // ROLE_USER (Agent)
            2 => 'MSP Admin',       // ROLE_ADMIN
            3 => 'MSP Reporter',    // ROLE_REPORTER
            4 => 'MSP Finance',     // ROLE_FINANCE
        ];

        foreach ($legacyToRbac as $legacyRole => $rbacRoleName) {
            $rbacRole = Role::where('name', $rbacRoleName)->first();
            if (!$rbacRole) {
                continue;
            }

            // Get all users with this legacy role who don't already have a role_user entry
            $userIds = DB::table('users')
                ->where('role', $legacyRole)
                ->pluck('id');

            foreach ($userIds as $userId) {
                DB::table('role_user')->insertOrIgnore([
                    'user_id' => $userId,
                    'role_id' => $rbacRole->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Also migrate any existing company_user.role_id assignments into role_user
        $companyUserRoles = DB::table('company_user')
            ->whereNotNull('role_id')
            ->select('user_id', 'role_id')
            ->distinct()
            ->get();

        foreach ($companyUserRoles as $cu) {
            DB::table('role_user')->insertOrIgnore([
                'user_id' => $cu->user_id,
                'role_id' => $cu->role_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // We don't reverse this migration — the legacy column still exists for reference
    }
};
