<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PermissionResource;
use App\Http\Traits\JsonResponseTrait;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionsController extends Controller
{
    use JsonResponseTrait;
    public function index()
    {
        $permissions = PermissionResource::collection(Permission::get());

        if ($permissions->isEmpty()) {
            return $this->notFoundResponse($permissions);
        }

        return $this->getAllData($permissions);
    }
    }
