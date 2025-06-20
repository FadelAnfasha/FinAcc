<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('auth');
    //     $this->middleware('permission:CreateUser');
    // }

    /**
     * Display the admin panel
     */
    public function index()
    {
        $roles = Role::with('permissions')->get()->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->toArray()
            ];
        });

        $permissions = Permission::all()->map(function ($permission) {
            return [
                'id' => $permission->id,
                'name' => $permission->name
            ];
        });

        $users = User::with('roles')->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'npk' => $user->npk,
                'role' => $user->roles->first()?->name ?? 'No Role'
            ];
        });

        return Inertia::render('admin/index', [
            'roles' => $roles,
            'permissions' => $permissions,
            'users' => $users
        ]);
    }

    /**
     * Store a new role
     */
    public function storeRole(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name'
        ]);

        DB::transaction(function () use ($request) {
            $role = Role::create(['name' => $request->name]);

            if ($request->has('permissions')) {
                $role->givePermissionTo($request->permissions);
            }
        });

        return redirect()->back()->with('success', 'Role created successfully');
    }

    /**
     * Update an existing role
     */
    public function updateRole(Request $request, Role $role)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles')->ignore($role->id)],
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name'
        ]);

        DB::transaction(function () use ($request, $role) {
            $role->update(['name' => $request->name]);

            // Sync permissions
            $role->syncPermissions($request->permissions ?? []);
        });

        return redirect()->back()->with('success', 'Role updated successfully');
    }

    /**
     * Delete a role
     */
    public function destroyRole(Role $role)
    {
        // Check if role is assigned to any users
        $usersCount = $role->users()->count();

        if ($usersCount > 0) {
            return redirect()->back()->with('error', 'Cannot delete role. It is assigned to ' . $usersCount . ' user(s).');
        }

        $role->delete();

        return redirect()->back()->with('success', 'Role deleted successfully');
    }

    /**
     * Store a new permission
     */
    public function storePermission(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name'
        ]);

        Permission::create(['name' => $request->name]);

        return redirect()->back()->with('success', 'Permission created successfully');
    }

    /**
     * Update an existing permission
     */
    public function updatePermission(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('permissions')->ignore($permission->id)]
        ]);

        $permission->update(['name' => $request->name]);

        return redirect()->back()->with('success', 'Permission updated successfully');
    }

    /**
     * Delete a permission
     */
    public function destroyPermission(Permission $permission)
    {
        // Check if permission is assigned to any roles
        $rolesCount = $permission->roles()->count();

        if ($rolesCount > 0) {
            return redirect()->back()->with('error', 'Cannot delete permission. It is assigned to ' . $rolesCount . ' role(s).');
        }

        $permission->delete();

        return redirect()->back()->with('success', 'Permission deleted successfully');
    }

    /**
     * Assign role to user
     */
    public function assignRole(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id'
        ]);

        $user = User::findOrFail($request->user_id);
        $role = Role::findOrFail($request->role_id);

        // Remove all existing roles and assign the new one
        $user->syncRoles([$role->name]);

        return redirect()->back()->with('success', 'Role assigned successfully');
    }

    /**
     * Remove role from user
     */
    public function removeRole(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $user = User::findOrFail($request->user_id);
        $user->syncRoles([]);

        return redirect()->back()->with('success', 'Role removed successfully');
    }

    /**
     * Get all roles for dropdown
     */
    public function getRoles()
    {
        return response()->json([
            'roles' => Role::all(['id', 'name'])
        ]);
    }

    /**
     * Get all permissions for dropdown
     */
    public function getPermissions()
    {
        return response()->json([
            'permissions' => Permission::all(['id', 'name'])
        ]);
    }

    /**
     * Get users with their roles
     */
    public function getUsers()
    {
        $users = User::with('roles')->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'npk' => $user->npk,
                'role' => $user->roles->first()?->name ?? 'No Role'
            ];
        });

        return response()->json(['users' => $users]);
    }
}
