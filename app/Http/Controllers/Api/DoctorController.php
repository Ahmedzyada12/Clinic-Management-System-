<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Reservation;
use Illuminate\Http\Request;
use App\Http\Traits\GeneralTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Traits\JsonResponseTrait;
use App\Rules\UniqueEmailAcrossTables;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DoctorController extends Controller
{

    use GeneralTrait;
    use JsonResponseTrait;
    public function index()
    {

        $users = User::with(['role.permissions', 'specialization'])->get();

        $users = $users->map(function ($user) {
            $permissions = collect([]);
            if ($user->role && $user->role->permissions) {
                $permissions = $user->role->permissions->mapWithKeys(function ($perm) {
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
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,

                'email' => $user->email,
                'phone' => $user->phone,

                'specialization' => [

                    'id' => $user->specialization ? $user->specialization->id : null,
                    'name' => $user->specialization ? $user->specialization->name : null,
                ],
                'role' => [
                    'id' => $user->role ? $user->role->id : null,
                    'name' => $user->role ? $user->role->name : null,
                    'permissions' => $permissions,
                ]

            ];
        });

        return response()->json([
            'data' => $users,
            'status' => 200
        ]);
    }

    public function getSuperAdminUsers()
    {
        // Retrieve users with the role 'super admin'
        $users = User::with(['role.permissions'])
            ->whereHas('role', function ($query) {
                $query->where('name', 'superAdmin');
            })
            ->get();

        // Map the users to the desired structure
        $users = $users->map(function ($user) {
            $permissions = collect([]);
            if ($user->role && $user->role->permissions) {
                $permissions = $user->role->permissions->mapWithKeys(function ($perm) {
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
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => [
                    'id' => $user->role ? $user->role->id : null,
                    'name' => $user->role ? $user->role->name : null,
                    'permissions' => $permissions,
                ]
            ];
        });

        return response()->json([
            'data' => $users,
            'status' => 200
        ]);
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name'    => 'required|max:255',
            'last_name'     => 'required|max:255',
            'email'         => ['required', 'string', 'email', 'max:255', new UniqueEmailAcrossTables],
            'phone'         => 'required|max:255',
            'image'         => 'nullable|mimes:png,jpg,jpeg',
            'password'      => 'required|max:255',
            'specialization_id' => 'nullable|exists:specializations,id',
            'role_id'       => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            $errorMessage = $validator->errors()->first();
            $response = [
                'status'  => 400,
                'message' => $errorMessage,
            ];
            return response()->json($response, 200);
        }
        $imagePath = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            Storage::disk('public')->put('images/' . $filename, file_get_contents($image));
            $imagePath = 'https://192.168.1.19/storage/images/' . $filename;
        }


        $doctor = User::create([
            "first_name" => $request->first_name,
            "last_name" => $request->last_name,
            'email' => $request->email,
            'specialization_id' => $request->specialization_id,
            'image' => $imagePath,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
        ]);

        return response()->json([
            "message" => 'Saved Successfully',
            "data" => $doctor,
            "status" => 200,
        ]);
    }

    public function getDoctorBySpecialization(Request $request, $specialization_id)
    {
        if (!$specialization_id) {
            return response()->json(['status' => 400, 'message' => 'Specialization ID is required']);
        }
        $doctors = User::with('appointments')->where('specialization_id', $specialization_id)->get();

        return response()->json([
            'data' => $doctors,
            'status' => 200
        ]);
    }

    public function searchDoctorByName(Request $request)
    {

        $name = $request->input('name');
        $specialization_id = $request->input('spec');
        $perPage = $request->input('per_page', 10);

        $doctors = User::where(function ($query) use ($name, $specialization_id) {
            if ($name) {
                $query->where(function ($q) use ($name) {
                    $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $name . '%'])
                        ->orWhereRaw("CONCAT(last_name, ' ', first_name) LIKE ?", ['%' . $name . '%']);
                });
            }
            if ($specialization_id) {
                $query->whereHas('specialization', function ($q) use ($specialization_id) {
                    $q->where('specialization_id', 'LIKE', '%' . $specialization_id . '%');
                });
            }
        })
            ->with('appointments:id,time_start,time_end,status,doctor_id', 'specialization')
            ->paginate($perPage);

        return response()->json([
            'status' => 200,
            'message' => 'Doctors found successfully',
            'data' => $doctors->items(),
            'current_page' => $doctors->currentPage(),
            'last_page' => $doctors->lastPage(),
            'per_page' => $doctors->perPage(),
            'total' => $doctors->total(),
        ]);
    }


    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'first_name'    => 'required|max:255',
            'last_name'    => 'required|max:255',
            'email'             => 'required|email|unique:users,email,' . $id,
            'phone'             => 'required|max:255',
            'image'             => 'nullable|mimes:png,jpg,jpeg',
            'password'          => 'nullable|max:255',
            'specialization_id' => 'required|exists:specializations,id',
            // 'consultant_price'  => 'required|numeric|min:0',
            // 'disclosure_price'  => 'required|numeric|min:0',
            'role_id'           => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            $errorMessage = $validator->errors()->first();
            return response()->json(['status' => 400, 'message' => $errorMessage], 200);
        }

        $user = User::find($id);
        if (!$user) {
            return response()->json(['status' => 404, 'message' => 'User not found'], 404);
        }

        // Handle file upload
        if ($request->hasFile('image')) {
            // Delete the old image if it exists
            if ($user->image) {
                Storage::disk('public')->delete(str_replace('https://192.168.1.19/storage/', '', $user->image));
            }
            // Store the new image
            $image = $request->file('image');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            Storage::disk('public')->put('images/' . $filename, file_get_contents($image));
            $user->image = 'https://192.168.1.19/storage/images/' . $filename;
        }

        // Update user attributes

        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->specialization_id = $request->specialization_id;
        // $user->imagePath = $request->imagePath;
        // $user->consultant_price = $request->consultant_price;
        // $user->disclosure_price = $request->disclosure_price;
        $user->role_id = $request->role_id;

        if ($request->password) {
            $user->password = Hash::make($request->password);
        }
        $user->save();
        $user->roles()->sync([$request->role_id]);

        return response()->json(['status' => 200, 'message' => 'User updated successfully', 'data' => $user]);
    }


    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user)
            return   $this->notFoundResponse();

        $user->delete();

        return $this->deleteResponse();
    }

    // public function show($id)
    // {
    //     $doctor = User::find($id);

    //     if (!$doctor) {
    //         return response()->json([
    //             'status' => 404,
    //             'message' => 'Doctor not found'
    //         ]);
    //     }
    //     $reservations = Reservation::whereHas('appointment', function ($query) use ($id) {
    //         $query->where('doctor_id', $id);
    //     })->get();

    //     return response()->json([
    //         'status' => 200,
    //         'message' => 'Doctor found successfully',
    //         'data' => $doctor,
    //         'reservations' => $reservations
    //     ]);
    // }

    public function show($id)
    {
        // Find the doctor by ID
        $doctor = User::with(['appointments'])->find($id);

        if (!$doctor) {
            return response()->json([
                'status' => 404,
                'message' => 'Doctor not found'
            ]);
        }

        $reservations = Reservation::whereHas('appointment', function ($query) use ($id) {
            $query->where('doctor_id', $id);
        })->with('appointment.examinationType')->get();

        // Format reservations to include examinationType details
        $formattedReservations = $reservations->map(function ($reservation) {
            return [
                'id' => $reservation->id,
                'patient_id' => $reservation->patient_id,
                'status' => $reservation->status,
                'created_at' => $reservation->created_at,
                'updated_at' => $reservation->updated_at,
                'examination_id' => $reservation->examination_id,
                'appointment_id' => $reservation->appointment_id,

                'examination_type' => $reservation->examination ? [
                    'id' => $reservation->examination->id,
                    'name' => $reservation->examination->name,
                    'amount' => $reservation->examination->amount,
                    'color' => $reservation->examination->color,
                ] : null,

            ];
        });

        return response()->json([
            'status' => 200,
            'message' => 'Doctor found successfully',
            'data' => $doctor,
            'reservations' => $formattedReservations
        ]);
    }
    // public function show($id)
    // {
    //     $doctor = User::with(['appointments.reservations'])->find($id);

    //     if (!$doctor) {
    //         return response()->json([
    //             'status' => 404,
    //             'message' => 'Doctor not found'
    //         ]);
    //     }

    //     // Collect all reservations from the appointments
    //     $reservations = $doctor->appointments->flatMap(function ($appointment) {
    //         return $appointment->reservations;
    //     });

    //     return response()->json([
    //         'status' => 200,
    //         'message' => 'Doctor found successfully',
    //         'data' => $doctor,
    //         'reservations' => $reservations
    //     ]);
    // }
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
}