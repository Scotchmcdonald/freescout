<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use Nwidart\Modules\Facades\Module;
use Illuminate\Support\Facades\Log;

/**
 * Idempotent RBAC Seeder — safe to run repeatedly.
 *
 * Three-pass approach:
 *   Pass 1: Register all permissions (core + module)
 *   Pass 2: Create/update all roles
 *   Pass 3: Assign baseline permissions to roles (syncWithoutDetaching)
 *
 * @see config/rbac.php for the authoritative definitions.
 */
class RbacSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array<string, mixed> $config */
        $config = config('rbac');

        $this->seedPermissions($config);
        $this->seedRoles($config);
        $this->seedRolePermissions($config);
    }

    /**
     * Pass 1: Register all permissions.
     *
     * @param array<string, mixed> $config
     */
    protected function seedPermissions(array $config): void
    {
        // ── Core permissions from config/rbac.php ──
        /** @var array<string, array{label: string, group?: string, sort_order?: int}> $corePerms */
        $corePerms = $config['core_permissions'];

        foreach ($corePerms as $name => $attrs) {
            Permission::register(
                name: $name,
                label: $attrs['label'],
                module: 'core',
                group: $attrs['group'] ?? null,
                sortOrder: $attrs['sort_order'] ?? 0,
            );
        }

        // ── Dynamic module permissions from module.json ──
        if (!class_exists(Module::class)) {
            return;
        }

        foreach (Module::allEnabled() as $module) {
            $alias = strtolower((string) $module->getAlias());
            $displayName = $module->getName();

            // Granular permissions declared in module.json "permissions" key
            $definedPermissions = $module->get('permissions', []);
            if (!is_array($definedPermissions)) {
                continue;
            }

            $order = 10;
            foreach ($definedPermissions as $permName => $permLabel) {
                Permission::register(
                    name: (string) $permName,
                    label: is_string($permLabel) ? $permLabel : null,
                    module: $alias,
                    group: $displayName,
                    sortOrder: $order,
                );
                $order += 10;
            }
        }

        Log::info('[RbacSeeder] Permissions synced — total: ' . Permission::count());
    }

    /**
     * Pass 2: Create/update all roles from config.
     *
     * @param array<string, mixed> $config
     */
    protected function seedRoles(array $config): void
    {
        /** @var array<string, array{label: string, is_super_admin?: bool, scope?: string, sort_order?: int}> $roles */
        $roles = $config['roles'];

        foreach ($roles as $name => $attrs) {
            $role = Role::firstOrCreate(
                ['name' => $name],
                [
                    'label'          => $attrs['label'],
                    'is_super_admin' => $attrs['is_super_admin'] ?? false,
                    'scope'          => $attrs['scope'] ?? 'internal',
                    'sort_order'     => $attrs['sort_order'] ?? 0,
                ]
            );

            // Update attributes if they changed in config
            $role->update([
                'label'          => $attrs['label'],
                'is_super_admin' => $attrs['is_super_admin'] ?? false,
                'scope'          => $attrs['scope'] ?? 'internal',
                'sort_order'     => $attrs['sort_order'] ?? 0,
            ]);
        }

        Log::info('[RbacSeeder] Roles synced — total: ' . Role::count());
    }

    /**
     * Pass 3: Assign baseline permissions to roles.
     *
     * Uses syncWithoutDetaching() so that permissions added manually via the UI
     * are never removed, but the baseline defined in config is always present.
     *
     * @param array<string, mixed> $config
     */
    protected function seedRolePermissions(array $config): void
    {
        /** @var array<string, string|list<string>> $rolePermissions */
        $rolePermissions = $config['role_permissions'];

        foreach ($rolePermissions as $roleName => $permissions) {
            $role = Role::where('name', $roleName)->first();
            if (!$role) {
                Log::warning("[RbacSeeder] Role '{$roleName}' not found — skipping assignment.");
                continue;
            }

            if ($permissions === '*') {
                // Super admin: sync ALL permissions
                $role->permissions()->sync(Permission::pluck('id'));
            } else {
                // Specific role: merge baseline permissions without removing extras
                $permissionIds = Permission::whereIn('name', $permissions)->pluck('id');
                $role->permissions()->syncWithoutDetaching($permissionIds);
            }
        }

        Log::info('[RbacSeeder] Role-permission assignments synced.');
    }
}
