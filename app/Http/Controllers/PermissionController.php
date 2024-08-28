<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index()
    {

        $permissions = Permission::get();
        if ($permissions->isEmpty()) {
            return response()->json(['message' => 'No found.', 'status' => 400]);
        }
        return response()->json([
            'data' =>   $permissions,
            "status" => 200
        ]);
    }

    public function store(Request $request)
    {
        $request->validate(['section' => 'required|unique:permissions']);

        $permission = Permission::create($request->all());
        return response()->json($permission, 201);
    }

    public function show(Permission $permission)
    {
        return $permission;
    }

    public function update(Request $request, Permission $permission)
    {
        $request->validate(['section' => 'required|unique:permissions,name,' . $permission->id]);

        $permission->update($request->all());
        return response()->json($permission);
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();
        return response()->json(null, 204);
    }
}