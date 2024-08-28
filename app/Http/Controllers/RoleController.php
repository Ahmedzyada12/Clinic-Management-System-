<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->get();

        $roles = $roles->map(function ($role) {
            $permissions = $role->permissions->mapWithKeys(function ($perm) {
                return [
                    $perm->section => [
                        'create' => $perm->pivot->create,
                        'read' => $perm->pivot->read,
                        'update' => $perm->pivot->update,
                        'delete' => $perm->pivot->delete,
                        'type' => $perm->pivot->type,
                    ]
                ];
            });
            return [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $permissions,
            ];
        });
        return response()->json($roles, 200);
    }
   
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:roles',
            'permissions' => 'nullable|array',
            'permissions.*' => 'required|array',
            'permissions.*.create' => 'boolean',
            'permissions.*.read' => 'boolean',
            'permissions.*.update' => 'boolean',
            'permissions.*.delete' => 'boolean',
            'permissions.*.type' => 'nullable|string|in:doctor,assistant,patient',
        ], [
            'name.required' => 'The role name is required.',
            'name.unique' => 'The role name must be unique.',
            'permissions.array' => 'Permissions must be an array.',
            'permissions.*.required' => 'Each permission must be an array.',
            'permissions.*.create.boolean' => 'The create permission must be a boolean.',
            'permissions.*.read.boolean' => 'The read permission must be a boolean.',
            'permissions.*.update.boolean' => 'The update permission must be a boolean.',
            'permissions.*.delete.boolean' => 'The delete permission must be a boolean.',
            'permissions.*.type.in' => 'The type must be one of doctor, assistant, patient.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $role = Role::create($request->only('name'));

        $permissions = $request->has('permissions') ? $request->permissions : [];

        foreach ($permissions as $section => $actions) {
            $permission = Permission::firstOrCreate([
                'section' => $section,
            ]);

            // Ensure 'type' is always set
            $role->permissions()->attach($permission->id, [
                'create' => $actions['create'],
                'read' => $actions['read'],
                'update' => $actions['update'],
                'delete' => $actions['delete'],
                'type' => $actions['type'] ?? null, // Set a default type if not provided
            ]);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Role created successfully',
            'data' => $role->load('permissions')
        ]);
    }

    public function show(Role $role)
    {
        $roleWithPermissions = $role->load('permissions');
        $permissions = $roleWithPermissions->permissions->mapWithKeys(function ($permission) {
            return [
                $permission->section => [
                    'create' => $permission->pivot->create,
                    'read' => $permission->pivot->read,
                    'update' => $permission->pivot->update,
                    'delete' => $permission->pivot->delete,
                    'type' => $permission->pivot->type,
                ]
            ];
        });

        return response()->json([
            'status' => 'success',
            'role' => [
                'id' => $roleWithPermissions->id,
                'name' => $roleWithPermissions->name,
                'permissions' => $permissions->toArray(),
            ]
        ]);
    }
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:roles,name,' . $id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'required|array',
            'permissions.*.create' => 'boolean',
            'permissions.*.read' => 'boolean',
            'permissions.*.update' => 'boolean',
            'permissions.*.delete' => 'boolean',
            'permissions.*.type' => 'nullable|string|in:doctor,assistant,patient',
        ], [
            'name.required' => 'The role name is required.',
            'name.unique' => 'The role name must be unique.',
            'permissions.array' => 'Permissions must be an array.',
            'permissions.*.required' => 'Each permission must be an array.',
            'permissions.*.create.boolean' => 'The create permission must be a boolean.',
            'permissions.*.read.boolean' => 'The read permission must be a boolean.',
            'permissions.*.update.boolean' => 'The update permission must be a boolean.',
            'permissions.*.delete.boolean' => 'The delete permission must be a boolean.',
            'permissions.*.type.in' => 'The type must be one of doctor, assistant, patient.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $role = Role::findOrFail($id);
        $role->update($request->only('name'));

        $role->permissions()->detach();

        $permissions = $request->has('permissions') ? $request->permissions : [];

        foreach ($permissions as $section => $actions) {
            $permission = Permission::firstOrCreate([
                'section' => $section,
            ]);

            // Ensure 'type' is always set
            $role->permissions()->attach($permission->id, [
                'create' => $actions['create'],
                'read' => $actions['read'],
                'update' => $actions['update'],
                'delete' => $actions['delete'],
                'type' => $actions['type'] ?? null, // Set a default type if not provided
            ]);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Role updated successfully',
            'data' => $role->load('permissions')
        ]);
    }


    public function destroy($id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }
        // Check if the role is attached to any patient, doctor, or assistant
        $isRoleUsed = DB::table('patients')->where('role_id', $id)->exists() ||
            DB::table('users')->where('role_id', $id)->exists() ||
            DB::table('assistants')->where('role_id', $id)->exists();

        if ($isRoleUsed) {
            return response()->json(
                [
                    'message' => 'Cannot delete role because it is attached to a user, patient, or assistant',
                    'status' => 'error',
                ]
            );
        }
        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully',
            'status' => 200,
            // 'message' => 'Role updated successfully',
        ]);
    }
}