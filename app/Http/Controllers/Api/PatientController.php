<?php


namespace App\Http\Controllers\Api;

use App\Models\Role;
use App\Models\User;
use App\Models\Patient;

use App\Models\ClinicInfo;
use App\Models\Reservation;
use Illuminate\Http\Request;
use App\Http\Traits\GeneralTrait;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Traits\JsonResponseTrait;
use App\Rules\UniqueEmailAcrossTables;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PatientController extends Controller
{
    use GeneralTrait;
    use JsonResponseTrait;

    public function index()
    {
        $patients = Patient::with(['role.permissions'])->get();

        if ($patients->isEmpty()) {
            return response()->json(['message' => 'No Patients found.', 'status' => 400]);
        }

        $patients = $patients->map(function ($patient) {
            $permissions = collect([]);
            if ($patient->role && $patient->role->permissions) {
                $permissions = $patient->role->permissions->mapWithKeys(function ($perm) {
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
                'id' => $patient->id,
                'first_name' => $patient->first_name,
                'last_name' => $patient->last_name,
                'email' => $patient->email,
                'phone' => $patient->phone,
                'image' => $patient->image,
                // 'doctor' => [
                //     'id' => $patient->doctor ? $patient->doctor->id : null,
                //     'first_name' => $patient->doctor ? $patient->doctor->first_name : null,
                //     'last_name' => $patient->doctor ? $patient->doctor->last_name : null,
                // ],
                'role' => [
                    'id' => $patient->role ? $patient->role->id : null,
                    'name' => $patient->role ? $patient->role->name : null,
                    'permissions' => $permissions,
                ]
            ];
        });
        return response()->json([
            'data' => $patients,
            'status' => 200
        ]);
    }
    public function countPatients()
    {
        try {
            $patientCount = Patient::count();
            return response()->json([
                'data' => $patientCount,
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error counting patients: ' . $e->getMessage(),
                'status' => 500
            ]);
        }
    }
    public function getPatientsByDoctor($doctorId)
    {
        $patients = Patient::where('doctor_id', $doctorId)
            ->get();

        return response()->json([
            'data' => $patients,
            'status' => 200
        ]);
    }
    public function searchPatientByName(Request $request)
    {

        $name = $request->input('name');
        $perPage = $request->input('per_page', 10);
        $patients = Patient::where(function ($query) use ($name) {
            if ($name) {
                $query->where(function ($q) use ($name) {
                    $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $name . '%'])
                        ->orWhereRaw("CONCAT(last_name, ' ', first_name) LIKE ?", ['%' . $name . '%']);
                });
            }
        })->paginate($perPage);

        return response()->json([
            'status' => 200,
            'message' => 'patient found successfully',
            'data' => $patients->items(),
            'current_page' => $patients->currentPage(),
            'last_page' => $patients->lastPage(),
            'per_page' => $patients->perPage(),
            'total' => $patients->total(),

        ]);
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name'    => 'required|max:255',
            'last_name'    => 'required|max:255',
            'email'         => ['required', 'string', 'email', 'max:255', new UniqueEmailAcrossTables],
            'phone'         => 'required|max:255',
            'image'         => 'nullable|mimes:png,jpg,jpeg',
            'password'      => 'required|max:255',
            // 'doctor_id' => "required|string|exists:users,id",
            // 'role_id' => 'required|exists:roles,id',
            'role' => 'patient',
        ]);

        if ($validator->fails()) {
            $errorMessage = $validator->errors()->first();
            $response = [
                'status'  => 400,
                'message' => $errorMessage,
            ];
            return response()->json($response, 200);
        }

        $role = Role::where('name', 'patient')->first();

        // Check if the role exists
        if (!$role) {
            return response()->json(['status' => 400, 'message' => 'Role not found']);
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            Storage::disk('public')->put('images/' . $filename, file_get_contents($image));
            $imagePath = asset('storage/images/' . $filename);
        }


        $patient = Patient::create([
            "first_name" => $request->first_name,
            "last_name" => $request->last_name,
            'email' => $request->email,
            // 'doctor_id' => $request->doctor_id,
            'image' => $imagePath,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role_id' => $role->id,
            'role' => 'patient',
        ]);

        $token = JWTAuth::fromUser($patient);
        $data = [
            'user' => $patient,
            'token' => $token,
            'role_name' => $patient->role
        ];

        return response()->json(["message" => 'Saved Successfully ', "data" => $data, "status" => 200]);
    }
    public function register_patient(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name'    => 'required|max:255',
            'last_name'     => 'required|max:255',
            'email'         => ['required', 'string', 'email', 'max:255', new UniqueEmailAcrossTables],
            'phone'         => 'required|max:255',
            'image'         => 'nullable|mimes:png,jpg,jpeg',
            'password'      => 'required|max:255',
        ]);

        if ($validator->fails()) {
            $errorMessage = $validator->errors()->first();
            return response()->json([
                'status'  => 400,
                'message' => $errorMessage,
            ], 200);
        }

        $role = Role::where('name', 'patient')->first();
        // Check if the role exists
        if (!$role) {
            return response()->json(['status' => 400, 'message' => 'Role not found']);
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            Storage::disk('public')->put('images/' . $filename, file_get_contents($image));
            $imagePath = asset('storage/images/' . $filename);
        }

        $patient = Patient::create([
            "first_name"   => $request->first_name,
            "last_name"    => $request->last_name,
            'email'        => $request->email,
            'image'        => $imagePath,
            'phone'        => $request->phone,
            'password'     => Hash::make($request->password),
            'role_id'      => $role->id,
        ]);

        // Include the role name inside the user object
        $patient->role = [
            'id' => $role->id,
            'name' => $role->name,
        ];
        $token = JWTAuth::fromUser($patient);
        $data = [
            'user' => $patient,
            'token' => $token,
        ];

        return response()->json([
            'status' => 200,
            'message' => 'created successfully',
            'data' => $data
        ]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(),  [
            'first_name'    => 'required|max:255',
            'last_name'    => 'required|max:255',
            'email' => 'required|email|unique:patients,email,' . $id,
            'phone' => 'required|max:255',
            'image' => 'nullable|mimes:png,jpg,jpeg',
            'password' => 'max:255',
            // 'doctor_id' => "required|string|exists:users,id",
            'role_id' => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            $errorMessage = $validator->errors()->first();
            $response = [
                'status'  => 400,
                'message' => $errorMessage,
            ];
            return response()->json($response, 200);
        }

        $patient = Patient::find($id);
        if (!$patient)
            return $this->notFoundResponse();

        $image = '';
        if ($request->hasFile('image'))
            $image = $this->UploadImage($request, 'users/patients', 'image');
        $patient->first_name = $request->first_name;
        $patient->last_name = $request->last_name;
        $patient->email = $request->email;
        $patient->phone = $request->phone;
        // $patient->doctor_id = $request->doctor_id;
        $patient->role_id = $request->role_id;
        if ($request->hasFile('image'))
            $patient->image = $image;
        if ($request->password)
            $patient->password = Hash::make($request->password);

        $patient->save();

        return $this->updateResponse($patient);
    }

    public function destroy($id)
    {
        $patient = Patient::find($id);
        if (!$patient)
            return $this->notFoundResponse();
        $patient->delete();
        return $this->deleteResponse();
    }

    public function show($id)
    {
        $patient = Patient::with(['reservations.appointment.examination_type'])->find($id);
        if (!$patient) {
            return $this->notFoundResponse();
        }

        $formattedReservations = $patient->reservations->map(function ($reservation) {
            return [
                'id' => $reservation->id,
                'patient_id' => $reservation->patient_id,
                'status' => $reservation->status,
                'created_at' => $reservation->created_at,
                'updated_at' => $reservation->updated_at,
                'examination_id' => $reservation->examination_id,
                'appointment_id' => $reservation->appointment_id,
                'examination_type' => $reservation->examination_type ? [
                    'id' => $reservation->examination_type->id,
                    'name' => $reservation->examination_type->name,
                    'amount' => $reservation->examination_type->amount,
                    'color' => $reservation->examination_type->color,
                    'doctor' => $reservation->examination_type->doctor ? [
                        'id' => $reservation->examination_type->doctor->id,
                        'first_name' => $reservation->examination_type->doctor->first_name,
                        'last_name' => $reservation->examination_type->doctor->last_name,
                    ] : null,
                ] : null,
            ];
        });

        return response()->json([
            'status' => 200,
            'message' => 'Patient found successfully',
            'data' => $patient,
            'reservations' => $formattedReservations
        ]);
    }

    // public function search(Request $request)
    // {
    //     $keyword = $request->keyword;
    //     $names = explode(' ', $request->keyword);
    //     $users = User::where('role', 0)->where(function ($query) use ($keyword, $names) {
    //         $query->where('first_name', 'LIKE', '%' . $names[0] . '%');
    //         if (isset($names[1]))
    //             $query->orWhere('last_name', 'LIKE', '%' . $names[1] . '%');
    //         $query->orWhere('email', 'LIKE', '%' . $keyword . '%');
    //         $query->orWhere('phone', 'LIKE', '%' . $keyword . '%');
    //     })->paginate(10);

    //     return $this->returnData(__('general.found_success'), 'data', $users);
    // }

    public function deleteAll(Request $request)
    {
        $rules = ['ids' => 'required'];
        $ids = explode(',', $request->ids);
        $result = Patient::whereIn('id', $ids)->delete();
        if ($result > 0)
            return $this->returnSuccessResponse(__('general.delete_all', ['num' => $result]));
        else
            return $this->returnErrorResponse(__('general.delete_error'));
    }

    // public function register(Request $request)
    // {
    //     $rules = [
    //         'first_name'    => 'required|max:255',
    //         'last_name'    => 'required|max:255',
    //         'email' => 'required|max:255|email|unique:users,email',
    //         'password' => 'required|max:255',
    //         'phone' => 'required|max:255',
    //         'image' => 'mimes:png,jpeg,jpg|max:2048',
    //         'address' => 'max:255',
    //         'date' => 'max:255',
    //         'weight' => 'max:255',
    //         // 'role_id' => 'required|exists:roles,id',
    //     ];

    //     $validator = $this->validationResponse($request, $rules);
    //     if ($validator) {
    //         return $validator;
    //     }

    //     $image = '';
    //     if ($request->hasFile('image')) {
    //         $image = $this->UploadImage($request, 'users/patients', 'image');
    //     }

    //     // Create the user
    //     $user = User::create([
    //         'first_name' => $request->first_name,
    //         'last_name' => $request->last_name,
    //         'email' => $request->email,
    //         'password' => Hash::make($request->password),
    //         'phone' => $request->phone,
    //         'image' => $image,
    //         'role_id' => 6, // Save role_id directly
    //     ]);

    //     // Create token
    //     $token = JWTAuth::fromUser($user);
    //     $data = [
    //         'user' => $user,
    //         'token' => $token,
    //         'role_name' => $user->role->name
    //     ];

    //     return response()->json(
    //         [
    //             'status' => 'success',
    //             'message' => 'created successfully',
    //             'token' => $token,
    //             'token_type' => 'bearer',
    //             'data' => $data
    //         ]
    //     );
    //     // return $this->successResponse($data, __('created successfully'));
    // }
    public function register(Request $request)
    {
        $rules = [
            'first_name'    => 'required|max:255',
            'last_name'     => 'required|max:255',
            'email'         => 'required|max:255|email|unique:users,email',
            'password'      => 'required|max:255',
            'phone'         => 'required|max:255',
            'image'         => 'mimes:png,jpeg,jpg|max:2048',
            'address'       => 'max:255',
            'date'          => 'max:255',
            'weight'        => 'max:255',
            'role_id' => 'required|exists:roles,id',
        ];

        $validator = $this->validationResponse($request, $rules);
        if ($validator) {
            return $validator;
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            Storage::disk('public')->put('images/' . $filename, file_get_contents($image));
            $imagePath = 'https://192.168.1.19/storage/images/' . $filename;
        }

        // Create the user
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'image' => $imagePath,
            'role_id' => $request->role_id,
        ]);

        $userWithDetails = User::with(['role.permissions'])->find($user->id);

        $permissions = collect([]);
        if ($userWithDetails->role && $userWithDetails->role->permissions) {
            $permissions = $userWithDetails->role->permissions->mapWithKeys(function ($permission) {
                return [
                    $permission->section => [
                        'create' => $permission->pivot->create,
                        'read' => $permission->pivot->read,
                        'update' => $permission->pivot->update,
                        'delete' => $permission->pivot->delete,
                    ]
                ];
            });
        }

        $token = JWTAuth::fromUser($user);
        $data = [
            'user' => [
                'id' => $userWithDetails->id,
                'first_name' => $userWithDetails->first_name,
                'last_name' => $userWithDetails->last_name,
                'email' => $userWithDetails->email,
                'phone' => $userWithDetails->phone,
                'image' => $userWithDetails->image,
                'role' => [
                    'id' => $userWithDetails->role ? $userWithDetails->role->id : null,
                    'name' => $userWithDetails->role ? $userWithDetails->role->name : null,
                    'permissions' => $permissions->toArray(),
                ]
            ],
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
        ];

        return response()->json([
            'status' => 200,
            'message' => 'created successfully',
            'data' => $data
        ]);
    }
    private function UploadImage($request, $path, $field)
    {
        if ($request->file($field)) {
            $file = $request->file($field);
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path($path), $filename);
            return $path . '/' . $filename;
        }
        return null;
    }
}