<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\PermissionMap;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionController extends Controller
{
    /**
     * Display a listing of the roles and permissions.
     */
    public function index(Request $request)
    {
        $roles = Role::all();
        $selectedRoleName = $request->input('role', $roles->first() ? $roles->first()->name : 'superadmin');

        $role = Role::findByName($selectedRoleName, 'web');
        $granted = $role->permissions->pluck('name')->toArray();

        $rolePermissions = [];
        foreach (PermissionMap::keys() as $key) {
            $rolePermissions[$key] = in_array($key, $granted);
        }

        return Inertia::render('Admin/Permissions', [
            'roles' => $roles,
            'selectedRole' => $selectedRoleName,
            'permissionsMap' => PermissionMap::grouped(),
            'rolePermissions' => $rolePermissions,
        ]);
    }

    /**
     * Update permissions for a specific role.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'role' => 'required|string|exists:roles,name',
            'permissions' => 'required|array',
        ]);

        $role = Role::findByName($validated['role'], 'web');

        // $validated['permissions'] is expected to be an object/array: { permissionName: boolean }
        // Example: { "view_posts": true, "edit_posts": false }

        $granted = collect($validated['permissions'])
            ->filter(fn ($v) => $v === true)
            ->keys()
            ->toArray();

        $role->syncPermissions($granted);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->back()->with('success', 'Permissions du rôle "'.ucfirst($role->name).'" enregistrées avec succès !');
    }
}
