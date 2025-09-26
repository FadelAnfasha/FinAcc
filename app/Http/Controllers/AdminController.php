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
            // Ambil semua nama role dan simpan sebagai array
            $roleNames = $user->roles->pluck('name')->toArray();

            return [
                'id' => $user->id,
                'name' => $user->name,
                'npk' => $user->npk,
                // Key diubah menjadi 'roles' (jamak) untuk menampung array
                // Jika tidak ada role, akan menampilkan array kosong []
                'roles' => $roleNames
            ];
        });

        return Inertia::render('admin/index', [
            'roles' => $roles,
            'permissions' => $permissions,
            'users' => $users
        ]);
    }

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

    public function storePermission(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name'
        ]);

        Permission::create(['name' => $request->name]);

        return redirect()->back()->with('success', 'Permission created successfully');
    }

    public function updatePermission(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('permissions')->ignore($permission->id)]
        ]);

        $permission->update(['name' => $request->name]);

        return redirect()->back()->with('success', 'Permission updated successfully');
    }

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

    public function assignRole(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'nullable|exists:roles,id',
        ]);

        $user = User::findOrFail($request->user_id);
        $roleIds = $request->role_ids ?? [];

        if (method_exists($user, 'syncRoles')) {
            $roleNames = Role::whereIn('id', $roleIds)->pluck('name');
            $user->syncRoles($roleNames);
        } elseif (method_exists($user, 'roles')) {
            $user->roles()->sync($roleIds);
        } else {
            return redirect()->back()->withErrors(['role_sync' => 'User model does not have role synchronization capabilities.']);
        }

        return redirect()->back()->with('success', 'Roles assigned successfully');
    }

    public function getRoles()
    {
        return response()->json([
            'roles' => Role::all(['id', 'name'])
        ]);
    }

    public function getPermissions()
    {
        return response()->json([
            'permissions' => Permission::all(['id', 'name'])
        ]);
    }

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
