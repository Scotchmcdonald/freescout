<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Cleans up the redundant auto-generated access_* permissions that were never
 * gated anywhere meaningful. Also corrects the group for System permissions
 * to "Settings" so they appear under the Settings accordion in the RBAC matrix.
 *
 * Safe to run repeatedly (idempotent).
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Remove all access_* permission assignments (cascade from permission_role)
        $accessIds = DB::table('permissions')
            ->where('name', 'like', 'access_%')
            ->pluck('id');

        if ($accessIds->isNotEmpty()) {
            DB::table('permission_role')->whereIn('permission_id', $accessIds)->delete();
            DB::table('permissions')->whereIn('id', $accessIds)->delete();
        }

        // 2. Move existing System-group core permissions to the Settings group
        DB::table('permissions')
            ->where('module', 'core')
            ->whereIn('name', ['access_admin_panel', 'manage_settings', 'manage_rbac'])
            ->update(['group' => 'Settings']);
    }

    public function down(): void
    {
        // No rollback for the access_* deletion — re-run RbacSeeder to restore if needed.
        DB::table('permissions')
            ->where('module', 'core')
            ->whereIn('name', ['access_admin_panel', 'manage_settings', 'manage_rbac'])
            ->update(['group' => 'System']);
    }
};
