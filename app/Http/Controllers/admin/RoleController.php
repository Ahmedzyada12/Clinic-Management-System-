<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\JsonResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
       use JsonResponseTrait;
    function __construct()
    {
        // $this->middleware(['permission:role-list|role-create|role-edit|role-delete'], ['only' => ['index', 'store']]);
        // $this->middleware(['permission:role-create'], ['only' => ['create', 'store']]);
        // $this->middleware(['permission:role-edit'], ['only' => ['edit', 'update']]);
        // $this->middleware(['permission:role-delete'], ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $roles = Role::orderBy('id', 'DESC')->paginate(10);
        if ($roles->isEmpty()) {
            return response()->json(['message' => 'No roles found.', 'status' => 400]);
        }
        return $this->getAllData($roles) ;
    }

    public function create()
    {
        $permission = Permission::get();
        return view('roles.create', compact('permission'));
    }

    public function store(Request $request)
    {

        $this->validate($request, [
            'name' => 'required|unique:roles,name',
            'permission' => 'required',
        ]);

        $role = Role::create(['name' => $request->name,"guard_name"=>"web"]);
        foreach ($request->permission as $permission) {
            DB::table('role_has_permissions')->insert([
                'permission_id' => $permission,
                'role_id' => $role->id,
            ]);
        }

        return $this->successResponse($role);
    }

    public function show($id)
    {
        $data['role'] = Role::find($id);
        $data['rolePermissions'] = Permission::join("role_has_permissions", "role_has_permissions.permission_id", "=", "permissions.id")
            ->where("role_has_permissions.role_id", $id)
            ->get();

        return $this->getAllData($data);
    }

    public function roleWithPermissions($id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        $permission = Permission::get();

        $rolePermissions = DB::table("role_has_permissions")
            ->where("role_has_permissions.role_id", $id)
            ->pluck('role_has_permissions.permission_id')
            ->toArray();

        return response()->json([
            'role' => $role,
            'permissions' => $permission,
            'rolePermissions' => $rolePermissions
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required',
            'permission' => 'required',
        ]);
         $role = Role::find($id);

        if ($role) {
            // Delete existing permissions
            $role->permissions()->detach();

            // Update role name
            $role->name = $request->name;
            $role->save();

            // Associate new permissions
            if ($request->has('permission')) {
                foreach ($request->permission as $permission) {
                    $role->permissions()->attach($permission);
                }
            }
        }
        return $this->updateResponse($role);
    }


    public function destroy($id)
    {
        $role = Role::find($id);

        if (!$role) {
            return $this->notFoundResponse("role not found");
        }

        $role->delete();

        return $this->deleteResponse();
    }



}