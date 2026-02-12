<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RbacController extends Controller
{
    public function index(): View
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all();
        
        $settingsController = app(\App\Http\Controllers\SettingsController::class);
        $sections = $settingsController->getSections();
        $currentSection = 'rbac';

        return view('rbac.matrix', compact('roles', 'permissions', 'sections', 'currentSection'));
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'permission_id' => 'required|exists:permissions,id',
            'attached' => 'required|boolean',
        ]);

        /** @var \App\Models\Role $role */
        $role = Role::findOrFail($request->role_id);
        
        if ($request->attached) {
            $role->permissions()->syncWithoutDetaching([$request->permission_id]);
        } else {
            $role->permissions()->detach($request->permission_id);
        }

        return response()->json(['success' => true]);
    }

    public function storeRole(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name|max:255',
            'label' => 'nullable|string|max:255',
        ]);

        Role::create([
            'name' => $request->name,
            'label' => $request->label ?? $request->name,
        ]);

        return redirect()->route('rbac.matrix')->with('success', 'Role created successfully.');
    }

    public function destroyRole(Role $role): RedirectResponse
    {
        // Optional: Check if role is in use before deleting
        // if ($role->users()->exists()) { ... }

        $role->delete();

        return redirect()->route('rbac.matrix')->with('success', 'Role deleted successfully.');
    }
}
