<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;

class RbacController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all();
        
        $settingsController = app(\App\Http\Controllers\SettingsController::class);
        $sections = $settingsController->getSections();
        $currentSection = 'rbac';

        return view('rbac.matrix', compact('roles', 'permissions', 'sections', 'currentSection'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'permission_id' => 'required|exists:permissions,id',
            'attached' => 'required|boolean',
        ]);

        $role = Role::find($request->role_id);
        
        if ($request->attached) {
            $role->permissions()->syncWithoutDetaching([$request->permission_id]);
        } else {
            $role->permissions()->detach($request->permission_id);
        }

        return response()->json(['success' => true]);
    }
}
