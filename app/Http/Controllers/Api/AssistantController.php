<?php

namespace App\Http\Controllers\Api;

use Log;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\admin\Assistant;
use App\Http\Traits\GeneralTrait;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Intervention\Image\Facades\Image;
use App\Http\Traits\JsonResponseTrait;
use App\Rules\UniqueEmailAcrossTables;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AssistantController extends Controller
{


    use GeneralTrait;
    use JsonResponseTrait;
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $assistants = Assistant::with(['doctor:id,first_name,last_name', 'role.permissions'])->paginate($perPage);

        if ($assistants->isEmpty()) {
            return response()->json(['message' => 'No Assistants found.', 'status' => 400]);
        }

        $assistants->getCollection()->transform(function ($assistant) {
            $permissions = collect([]);
            if ($assistant->role && $assistant->role->permissions) {
                $permissions = $assistant->role->permissions->mapWithKeys(function ($perm) {
                    return [
                        $perm->section => [
                            'create' => $perm->pivot->create,
                            'read' => $perm->pivot->read,
                            'update' => $perm->pivot->update,
                            'delete' => $perm->pivot->delete,
                        ]
                    ];
                });
            }
            return [
                'id' => $assistant->id,
                'first_name' => $assistant->first_name,
                'last_name' => $assistant->last_name,
                'email' => $assistant->email,
                'phone' => $assistant->phone,
                'image' => $assistant->image,
                'doctor' => [
                    'id' => $assistant->doctor ? $assistant->doctor->id : null,
                    'first_name' => $assistant->doctor->first_name,
                    'last_name' => $assistant->doctor->last_name,
                ],
                'role' => [
                    'id' => $assistant->role ? $assistant->role->id : null,
                    'name' => $assistant->role ? $assistant->role->name : null,
                    'permissions' => $permissions,
                ]
            ];
        });

        return response()->json([
            'data' => $assistants->items(), // Current page's items
            'current_page' => $assistants->currentPage(),
            'last_page' => $assistants->lastPage(),
            'per_page' => $assistants->perPage(),
            'total' => $assistants->total(),
            'status' => 200
        ]);
    }
    public function countAssistants()
    {
        $assistantCount = Assistant::count();
        return response()->json([
            'assistant_count' => $assistantCount,
            'status' => 200
        ]);
    }
    

    // public function store(Request $request)
    // {
    //     $startTime = microtime(true);

    //     $validator = Validator::make($request->all(), [
    //         'first_name'    => 'required|max:255',
    //         'last_name'    => 'required|max:255',
    //         'email'         => ['required', 'string', 'email', 'max:255', new UniqueEmailAcrossTables],
    //         'phone'         => 'required|max:255',
    //         'image'         => 'nullable|mimes:png,jpg,jpeg',
    //         'password'      => 'required|max:255',
    //         'doctor_id' => 'required|string|exists:users,id',
    //         'role_id' => 'required|exists:roles,id',
    //     ]);

    //     if ($validator->fails()) {
    //         $errorMessage = $validator->errors()->first();
    //         $response = [
    //             'status'  => 400,
    //             'message' => $errorMessage,
    //         ];
    //         return response()->json($response, 200);
    //     }

    //     $image = '';
    //     if ($request->hasFile('image')) {
    //         $image = $this->UploadImage($request, 'users/assistants', 'image');
    //     }

    //     $assistant = Assistant::create([
    //         "first_name" => $request->first_name,
    //         "last_name" => $request->last_name,
    //         'email' => $request->email,
    //         'doctor_id' => $request->doctor_id,
    //         'image' => $image,
    //         'phone' => $request->phone,
    //         'password' => Hash::make($request->password),
    //         'role_id' => $request->role_id, // Include role_id in the creation
    //     ]);

    //     $endTime = microtime(true);
    //     $executionTime = $endTime - $startTime;

    //     return response()->json([
    //         'message' => 'Saved Successfully',
    //         'data' => $assistant,
    //         'status' => 200,
    //         'execution_time' => $executionTime
    //     ]);
    // }
    // public function store(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'first_name'    => 'required|max:255',
    //         'last_name'    => 'required|max:255',
    //         'email'         => ['required', 'string', 'email', 'max:255', new UniqueEmailAcrossTables],
    //         'phone'         => 'required|max:255',
    //         'image'         => 'nullable|mimes:png,jpg,jpeg',
    //         'password'      => 'required|max:255',
    //         'role'          => 'assisstant', // Similar to patient role
    //         'doctor_id'     => 'required|string|exists:users,id',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status'  => 400,
    //             'message' => $validator->errors()->first(),
    //         ], 200);
    //     }

    //     $role = Role::where('name', 'assisstant')->first(); // Fetching the role for assistant

    //     if (!$role) {
    //         return response()->json(['status' => 400, 'message' => 'Role not found']);
    //     }

    //     $image = '';
    //     if ($request->hasFile('image')) {
    //         $image = $this->UploadImage($request, 'users/assistants', 'image');
    //     }

    //     $assistant = Assistant::create([
    //         "first_name" => $request->first_name,
    //         "last_name" => $request->last_name,
    //         'email' => $request->email,
    //         'image' => $image,
    //         'phone' => $request->phone,
    //         'password' => Hash::make($request->password),
    //         'role_id' => $role->id,
    //         'doctor_id' => $request->doctor_id,
    //         'role' => 'assisstant',
    //     ]);

    //     $token = JWTAuth::fromUser($assistant);
    //     $data = [
    //         'user' => $assistant,
    //         'token' => $token,
    //         'role_name' => $assistant->role
    //     ];

    //     return response()->json(["message" => 'Saved Successfully', "data" => $data, "status" => 200]);
    // }
    // public function store(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'first_name'    => 'required|max:255',
    //         'last_name'    => 'required|max:255',
    //         'email'         => ['required', 'string', 'email', 'max:255', new UniqueEmailAcrossTables],
    //         'phone'         => 'required|max:255',
    //         'image'         => 'nullable|mimes:png,jpg,jpeg',
    //         'password'      => 'required|max:255',
    //         'doctor_id'     => 'required|string|exists:users,id',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status'  => 400,
    //             'message' => $validator->errors()->first(),
    //         ], 200);
    //     }

    //     // Fetching the role for assistant and caching it
    //     $role = Cache::remember('role_assistant', 60, function () {
    //         return Role::where('name', 'assisstant')->select('id')->first();
    //     });

    //     if (!$role) {
    //         return response()->json(['status' => 400, 'message' => 'Role not found']);
    //     }

    //     $image = '';
    //     if ($request->hasFile('image')) {
    //         $image = $this->UploadImage($request, 'users/assistants', 'image', [300, 300]); // Resize image for optimization
    //     }

    //     // Creating the assistant record
    //     $assistant = Assistant::create([
    //         "first_name" => $request->first_name,
    //         "last_name" => $request->last_name,
    //         'email' => $request->email,
    //         'image' => $image,
    //         'phone' => $request->phone,
    //         'password' => Hash::make($request->password, ['rounds' => 10]), // Using optimized bcrypt
    //         'role_id' => $role->id,
    //         'doctor_id' => $request->doctor_id,
    //         'role' => 'assisstant',
    //     ]);

    //     $token = JWTAuth::fromUser($assistant);
    //     $data = [
    //         'user' => $assistant,
    //         'token' => $token,
    //         'role_name' => $assistant->role
    //     ];

    //     return response()->json(["message" => 'Saved Successfully', "data" => $data, "status" => 200]);
    // }
    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'first_name'    => 'required|max:255',
            'last_name'     => 'required|max:255',
            'email'         => ['required', 'string', 'email', 'max:255', new UniqueEmailAcrossTables],
            'phone'         => 'required|max:255',
            'image'         => 'nullable|mimes:png,jpg,jpeg',
            'password'      => 'required|max:255',
            'doctor_id'     => 'required|string|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 400,
                'message' => $validator->errors()->first(),
            ], 200);
        }

        $role = Cache::remember('role_assistant', 60 * 60, function () {
            return Role::where('name', 'assisstant')->select('id')->first();
        });

        if (!$role) {
            return response()->json(['status' => 400, 'message' => 'Role not found']);
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            Storage::disk('public')->put('images/' . $filename, file_get_contents($image));
            $imagePath = 'https://192.168.1.19/storage/images/' . $filename;
        }

        $assistant = Assistant::create([
            "first_name" => $request->first_name,
            "last_name" => $request->last_name,
            'email' => $request->email,
            'image' => $imagePath,
            'phone' => $request->phone,
            'password' => Hash::make($request->password, ['rounds' => 10]), // Using optimized bcrypt
            'role_id' => $role->id,
            'doctor_id' => $request->doctor_id,
            'role' => 'assisstant',
        ]);
        $token = JWTAuth::fromUser($assistant);

        // Step 6: Preparing Response
        $data = [
            'user' => $assistant,
            'token' => $token,
            'role_name' => $assistant->role
        ];

        return response()->json(["message" => 'Saved Successfully', "data" => $data, "status" => 200]);
    }


    // public function store(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'first_name'    => 'required|max:255',
    //         'last_name'     => 'required|max:255',
    //         'email'         => ['required', 'string', 'email', 'max:255', new UniqueEmailAcrossTables],
    //         'phone'         => 'required|max:255',
    //         'image'         => 'nullable|mimes:png,jpg,jpeg',
    //         'password'      => 'required|max:255',
    //         'doctor_id'     => 'required|string|exists:users,id',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status'  => 400,
    //             'message' => $validator->errors()->first(),
    //         ], 200);
    //     }

    //     $role = Cache::remember('role_assistant', 60 * 60, function () {
    //         return Role::where('name', 'assistant')->select('id')->first();
    //     });

    //     if (!$role) {
    //         return response()->json(['status' => 400, 'message' => 'Role not found']);
    //     }

    //     $image = '';
    //     if ($request->hasFile('image')) {
    //         $image = $this->processImage($request->file('image'));
    //     }

    //     $assistant = Assistant::create([
    //         "first_name" => $request->first_name,
    //         "last_name" => $request->last_name,
    //         'email' => $request->email,
    //         'image' => $image,
    //         'phone' => $request->phone,
    //         'password' => Hash::make($request->password, ['rounds' => 10]), // Using optimized bcrypt
    //         'role_id' => $role->id,
    //         'doctor_id' => $request->doctor_id,
    //         'role' => 'assistant',
    //     ]);

    //     $token = JWTAuth::fromUser($assistant);

    //     // Preparing Response
    //     $data = [
    //         'user' => $assistant,
    //         'token' => $token,
    //         'role_name' => $assistant->role
    //     ];

    //     return response()->json(["message" => 'Saved Successfully', "data" => $data, "status" => 200]);
    // }


    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'first_name'    => 'required|max:255',
            'last_name'    => 'required|max:255',
            'email' => 'required|email|unique:assistants,email,' . $id,
            'phone' => 'required|max:255',
            'image' => 'nullable|mimes:png,jpg,jpeg',
            'password' => 'max:255',
            'doctor_id' => 'required|string|exists:users,id',
            'role_id' => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            $errorMessage = $validator->errors()->first();
            $response = [
                'status' => 400,
                'message' => $errorMessage,
            ];
            return response()->json($response, 200);
        }

        $assistant = Assistant::find($id);
        if (!$assistant) {
            return response()->json(['message' => 'Assistant not found.', 'status' => 404], 404);
        }

        $image = '';
        if ($request->hasFile('image')) {
            $image = $this->UploadImage($request, 'users/assistants', 'image');
            $assistant->image = $image;
        }

        $assistant->first_name = $request->first_name;
        $assistant->last_name = $request->last_name;
        $assistant->email = $request->email;

        $assistant->phone = $request->phone;
        $assistant->doctor_id = $request->doctor_id;
        $assistant->role_id = $request->role_id;
        if ($request->password) {
            $assistant->password = Hash::make($request->password);
        }
        $assistant->save();

        return response()->json([
            'message' => 'Assistant updated successfully.',
            'data' => $assistant,
            'status' => 200
        ]);
    }

    public function destroy($id)
    {
        $assistant = Assistant::find($id);
        if (!$assistant)
            return   $this->notFoundResponse();

        $assistant->delete();

        return $this->deleteResponse();
    }

    public function show($id)
    {
        $assistant = Assistant::find($id);
        if (!$assistant) {
            return $this->notFoundResponse();
        }
        return $this->getAllData($assistant);
    }

    public function search(Request $request)
    {
        $keyword = $request->keyword;
        $names = explode(' ', $request->keyword);
        $users = User::where('role', 0)->where(function ($query) use ($keyword, $names) {
            $query->where('first_name', 'LIKE', '%' . $names[0] . '%');
            if (isset($names[1]))
                $query->orWhere('last_name', 'LIKE', '%' . $names[1] . '%');
            $query->orWhere('email', 'LIKE', '%' . $keyword . '%');
            $query->orWhere('phone', 'LIKE', '%' . $keyword . '%');
        })->paginate(10);


        return $this->returnData(__('general.found_success'), 'data', $users);
    }

    public function deleteAll(Request $request)
    {
        $rules = ['ids' => 'required'];
        $ids = explode(',', $request->ids);
        $result = User::whereIn('id', $ids)->where('role', 1)->delete();
        if ($result > 0)
            return $this->returnSuccessResponse(__('general.delete_all', ['num' => $result]));
        else
            return $this->returnErrorResponse(__('general.delete_error'));
    }
}