<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataTransferObjects\StoreRoleData;
use App\DataTransferObjects\UpdateRolePermissionData;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRolePermissionRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RbacController extends Controller
{
    /**
     * Display the RBAC permission matrix with accordion-grouped permissions.
     */
    public function index(): View
    {
        $roles = Role::with('permissions')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $permissions = Permission::orderBy('module')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Group permissions by module for accordion display
        $groupedPermissions = $permissions->groupBy(function ($permission) {
            return $permission->module ?? 'other';
        })->sortKeys();

        // Build the matrix: { roleId: { permissionId: true } }
        $matrix = [];
        foreach ($roles as $role) {
            $matrix[$role->id] = [];
            foreach ($role->permissions as $permission) {
                $matrix[$role->id][$permission->id] = true;
            }
        }

        // Build module labels map
        $moduleLabels = $this->getModuleLabels();

        $settingsController = app(\App\Http\Controllers\SettingsController::class);
        $sections = $settingsController->getSections();
        $currentSection = 'rbac';

        return view('rbac.matrix', compact(
            'roles',
            'permissions',
            'groupedPermissions',
            'matrix',
            'moduleLabels',
            'sections',
            'currentSection',
        ));
    }

    /**
     * Toggle a permission on/off for a role.
     */
    public function update(UpdateRolePermissionRequest $request): JsonResponse
    {
        $dto = UpdateRolePermissionData::fromRequest($request);

        /** @var Role $role */
        $role = Role::findOrFail($dto->roleId);

        // Prevent modifying super admin role permissions
        if ($role->is_super_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Super admin role permissions cannot be modified.',
            ], 422);
        }

        if ($dto->attached) {
            $role->permissions()->syncWithoutDetaching([$dto->permissionId]);
        } else {
            $role->permissions()->detach($dto->permissionId);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Create a new role.
     */
    public function storeRole(StoreRoleRequest $request): RedirectResponse
    {
        $dto = StoreRoleData::fromRequest($request);

        // is_super_admin is a derived/computed field — set by the controller, not the DTO
        Role::create([
            'name'           => $dto->name,
            'label'          => $dto->label,
            'scope'          => $dto->scope,
            'is_super_admin' => false,
        ]);

        return redirect()->route('rbac.matrix')->with('success', 'Role created successfully.');
    }

    /**
     * Delete a role.
     */
    public function destroyRole(Role $role): RedirectResponse
    {
        // Prevent deleting super admin role
        if ($role->is_super_admin) {
            return redirect()->route('rbac.matrix')
                ->with('error', 'Cannot delete the super admin role.');
        }

        // Warn if role has users
        $userCount = $role->users()->count();
        if ($userCount > 0) {
            return redirect()->route('rbac.matrix')
                ->with('error', "Cannot delete role '{$role->name}' — it is assigned to {$userCount} user(s). Reassign them first.");
        }

        $role->delete();

        return redirect()->route('rbac.matrix')->with('success', 'Role deleted successfully.');
    }

    /**
     * Get human-readable labels for module keys.
     *
     * @return array<string, string>
     */
    protected function getModuleLabels(): array
    {
        $labels = [
            'core' => 'Core Permissions',
            'other' => 'Other',
        ];

        if (class_exists(\Nwidart\Modules\Facades\Module::class)) {
            foreach (\Nwidart\Modules\Facades\Module::allEnabled() as $module) {
                $alias = strtolower((string) $module->getAlias());
                $labels[$alias] = $module->get('display_name', $module->getName());
            }
        }

        return $labels;
    }
}
