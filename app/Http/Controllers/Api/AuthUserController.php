<?php

namespace App\Http\Controllers\api;

use App\Models\User;
use App\Models\Patient;
use App\Models\Assistant;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthUserController extends Controller
{
    public function __construct()
    {
        # By default we are using here auth:api middleware
        $this->middleware('auth:api', ['except' => ['login']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        // Validate incoming request
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $credentials = $request->only('email', 'password');

        // البحث عن المستخدم بناءً على البريد الإلكتروني
        $user = \App\Models\User::where('email', $request->email)->first();
        $patient = \App\Models\Patient::where('email', $request->email)->first();
        $assistant = \App\Models\admin\Assistant::where('email', $request->email)->first();

        if ($user) {
            $provider = 'users';
            $model = \App\Models\User::class;
        } elseif ($patient) {
            $provider = 'patients';
            $model = \App\Models\Patient::class;
        } elseif ($assistant) {
            $provider = 'assistants';  // تأكد من أن لديك إعداد auth provider لهذا الاسم
            $model = \App\Models\admin\Assistant::class;
        } else {

            return response()->json([
                'status' => 400,
                'message' => 'Email & Password does not match with our record.'
            ]);
        }

        // محاولة التحقق من الاعتماديات
        if (!$token = auth($provider)->attempt($credentials)) {

            return response()->json([
                'status' => 400,
                'message' => 'Email & Password does not match with our record.'
            ]);
        }

        // جلب المستخدم الذي تم تسجيل دخوله
        $authenticatedUser = auth($provider)->user();
        $userWithDetails = $model::with(['role.permissions',])->find($authenticatedUser->id);

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

        return response()->json([
            'status' => 200,
            'message' => 'Logged in successfully',
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth($provider)->factory()->getTTL() * 60,
            'user' => [
                'id' => $userWithDetails->id,
                'first_name' => $userWithDetails->first_name,
                'last_name' => $userWithDetails->last_name,
                'email' => $userWithDetails->email,
                'phone' => $userWithDetails->phone,
                'consultant_price' => $userWithDetails->consultant_price ?? null,
                'disclosure_price' => $userWithDetails->disclosure_price ?? null,
                'specialization' => [
                    'id' => $userWithDetails->specialization ? $userWithDetails->specialization->id : null,
                    'name' => $userWithDetails->specialization ? $userWithDetails->specialization->name : null,
                ],
                'role' => [
                    'id' => $userWithDetails->role ? $userWithDetails->role->id : null,
                    'name' => $userWithDetails->role ? $userWithDetails->role->name : null,
                    'permissions' => $permissions->toArray(),
                ]
            ]
        ]);
    }



    // public function login(Request $request)
    // {

    //     $credentials = request(['email', 'password']);

    //     if (!$token = auth()->attempt($credentials)) {
    //         return response()->json(['error' => 'Unauthorized'], 401);
    //     }

    //     $user = Auth::guard('api')->user();
    //     $doctor = User::with(['role.permissions', 'specialization'])->find($user->id);

    //     $permissions = collect([]);
    //     if ($doctor->role && $doctor->role->permissions) {
    //         $permissions = $doctor->role->permissions->mapWithKeys(function ($permission) {
    //             return [
    //                 $permission->section => [
    //                     'create' => $permission->pivot->create,
    //                     'read' => $permission->pivot->read,
    //                     'update' => $permission->pivot->update,
    //                     'delete' => $permission->pivot->delete,
    //                 ]
    //             ];
    //         });
    //     }
    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'Logged in successfully',
    //         'token' => $token,
    //         'token_type' => 'bearer',
    //         'expires_in' => auth()->factory()->getTTL() * 60,
    //         // 'user' => [
    //         //     'id' => $doctor->id,
    //         //     'full_name' => $doctor->full_name,
    //         //     'email' => $doctor->email,
    //         //     'phone' => $doctor->phone,
    //         //     'consultant_price' => $doctor->consultant_price,
    //         //     'disclosure_price' => $doctor->disclosure_price,
    //         //     'specialization' => [
    //         //         'id' => $doctor->specialization ? $doctor->specialization->id : null,
    //         //         'name' => $doctor->specialization ? $doctor->specialization->name : null,
    //         //     ],
    //         //     'role' => [
    //         //         'id' => $doctor->role ? $doctor->role->id : null,
    //         //         'name' => $doctor->role ? $doctor->role->name : null,
    //         //         'permissions' =>  $permissions->toArray(),
    //         //     ]
    //         // ]
    //     ]);
    // }
    // public function login(Request $request)
    // {
    //     $credentials = request(['email', 'password']);

    //     if (!$token = auth()->attempt($credentials)) {
    //         return response()->json(['error' => 'Unauthorized'], 401);
    //     }

    //     $user = Auth::guard('api')->user();
    //     $doctor = User::with(['role.permissions', 'specialization', 'reservations'])->find($user->id);

    //     $permissions = collect([]);
    //     if ($doctor->role && $doctor->role->permissions) {
    //         $permissions = $doctor->role->permissions->mapWithKeys(function ($permission) {
    //             return [
    //                 $permission->section => [
    //                     'create' => $permission->pivot->create,
    //                     'read' => $permission->pivot->read,
    //                     'update' => $permission->pivot->update,
    //                     'delete' => $permission->pivot->delete,
    //                 ]
    //             ];
    //         });
    //     }
    //     // Format reservations
    //     $reservations = $doctor->reservations->map(function ($reservation) {
    //         return [
    //             'id' => $reservation->id,
    //             'appointment_id' => $reservation->appointment_id,
    //             'patient_id' => $reservation->patient_id,
    //             'examination_id' => $reservation->examination_id,
    //             'status' => $reservation->status,
    //             'appointment' => [
    //                 'from' => $reservation->appointment->from,
    //                 'to' => $reservation->appointment->to,
    //             ],
    //             'examination' => [
    //                 'name' => $reservation->examination->name,
    //                 'amount' => $reservation->examination->amount,
    //             ],
    //         ];
    //     });

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'Logged in successfully',
    //         'token' => $token,
    //         'token_type' => 'bearer',
    //         'expires_in' => auth()->factory()->getTTL() * 60,
    //         'user' => [
    //             'id' => $doctor->id,
    //             'full_name' => $doctor->full_name,
    //             'email' => $doctor->email,
    //             'phone' => $doctor->phone,
    //             'consultant_price' => $doctor->consultant_price,
    //             'disclosure_price' => $doctor->disclosure_price,
    //             'specialization' => [
    //                 'id' => $doctor->specialization ? $doctor->specialization->id : null,
    //                 'name' => $doctor->specialization ? $doctor->specialization->name : null,
    //             ],
    //             'role' => [
    //                 'id' => $doctor->role ? $doctor->role->id : null,
    //                 'name' => $doctor->role ? $doctor->role->name : null,
    //                 'permissions' =>  $permissions->toArray(),
    //             ],
    //             'reservations' => $reservations,
    //         ]
    //     ]);
    // }
    // public function login(Request $request)
    // {
    //     $request->validate([
    //         'email' => 'required|string|email',
    //         'password' => 'required|string',
    //         'role' => 'required|string|in:patient,assistant,doctor',
    //     ]);

    //     $credentials = $request->only('email', 'password');
    //     $role = $request->role;

    //     switch ($role) {
    //         case 'patient':
    //             $user = Patient::where('email', $credentials['email'])->first();
    //             break;
    //         case 'assistant':
    //             $user = Assistant::where('email', $credentials['email'])->first();
    //             break;
    //         case 'doctor':
    //             $user = User ::where('email', $credentials['email'])->first();
    //             break;
    //         default:
    //             return response()->json(['error' => 'Invalid role'], 400);
    //     }

    //     if ($user && Hash::check($credentials['password'], $user->password)) {
    //         $token = $user->createToken('authToken')->plainTextToken;
    //         return response()->json(['token' => $token], 200);
    //     }

    //     return response()->json(['error' => 'Invalid credentials'], 401);
    // }

    public function register(Request $request)
    {
        $rules = [
            'full_name' => 'required|max:255',
            'email' => 'required|max:255|email|unique:users,email',
            'password' => 'required|max:255',
            'phone' => 'required|max:255',
            'image' => 'mimes:png,jpeg,jpg|max:2048',
            'address' => 'max:255',
            'date' => 'max:255',
            'weight' => 'max:255',
            'specialization_id' => 'nullable|exists:specializations,id',
            'consultant_price' => 'nullable|numeric|min:0',
            'disclosure_price' => 'nullable|numeric|min:0',
            'role_id' => 'required|exists:roles,id',
        ];

        $validator = $this->validationResponse($request, $rules);
        if ($validator) {
            return $validator;
        }

        $image = '';
        if ($request->hasFile('image')) {
            $image = $this->UploadImage($request, 'users/patients', 'image');
        }

        // Create the user
        $user = User::create([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'image' => $image,
            'specialization_id' => $request->specialization_id,
            'consultant_price' => $request->consultant_price,
            'disclosure_price' => $request->disclosure_price,
            'role_id' => $request->role_id,
        ]);

        // Create token
        $token = JWTAuth::fromUser($user);
        $data = [
            'user' => $user,
            'token' => $token,
            'role_name' => $user->role->name
        ];

        return $this->successResponse($data, __('created successfully'));
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

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout(); # This is just logout function that will destroy access token of current user

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        # When access token will be expired, we are going to generate a new one wit this function 
        # and return it here in response
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        # This function is used to make JSON response with new
        # access token of current user
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }
}