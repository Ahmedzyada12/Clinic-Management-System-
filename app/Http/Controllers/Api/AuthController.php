<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\ClinicInfo;
use Tymon\JWTAuth\JWTAuth;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Mail\PasswordResetMail;
use App\Mail\ResetPasswordCode;
use App\Http\Traits\GeneralTrait;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Http\Traits\JsonResponseTrait;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    use GeneralTrait, JsonResponseTrait;

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'requestPasswordReset', 'passwordReset', 'requestCodeResetPassword', 'checkTheCodePasswordReset', 'passwordResetWithCode']]);
    }

    /**
     * Log in a user and return a token.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $token = Auth::guard('api')->attempt($request->only('email', 'password'));
        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email & Password does not match with our record.'
            ], 401);
        }

        $user = Auth::guard('api')->user();
        $doctor = User::with(['role.permissions', 'specialization'])->find($user->id);

        $permissions = collect([]);
        if ($doctor->role && $doctor->role->permissions) {
            $permissions = $doctor->role->permissions->map(function ($permission) {
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
            'status' => 'success',
            'message' => 'Logged in successfully',
            'token' => $token,
            'user' => [
                'id' => $doctor->id,
                'full_name' => $doctor->full_name,
                'email' => $doctor->email,
                'phone' => $doctor->phone,
                'consultant_price' => $doctor->consultant_price,
                'disclosure_price' => $doctor->disclosure_price,
                'specialization' => [
                    'id' => $doctor->specialization ? $doctor->specialization->id : null,
                    'name' => $doctor->specialization ? $doctor->specialization->name : null,
                ],
                'role' => [
                    'id' => $doctor->role ? $doctor->role->id : null,
                    'name' => $doctor->role ? $doctor->role->name : null,
                    'permissions' => $permissions,
                ]
            ]
        ]);
    }

    // public function register(Request $request)
    // {

    //     return $request;
    //     try {
    //         //Validated
    //         $validateUser = Validator::make(
    //             $request->all(),
    //             [
    //                 'full_name' => 'required',
    //                 'email' => 'required|email|unique:users,email',
    //                 'password' => 'required',
    //                 'phone' => 'required',
    //             ]
    //         );

    //         if ($validateUser->fails()) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'validation error',
    //                 'errors' => $validateUser->errors()
    //             ], 401);
    //         }

    //         $user = User::create([
    //             'full_name' => $request->name,
    //             'email' => $request->email,
    //             'password' => Hash::make($request->password),
    //             'phone' =>  $request->phone,

    //         ]);

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'User Created Successfully',
    //             'token' => $user->createToken("API TOKEN")->plainTextToken
    //         ], 200);
    //     } catch (\Throwable $th) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $th->getMessage()
    //         ], 500);
    //     }
    // }

    public function test(){
        return "test";
    }
    /**
     * Check if the subdomain exists.
     */
    public function checkSubdomain(Request $request)
    {
        $subdomain = $request->route('subdomain');
        $data = DB::connection('mysql_kyan')->table('domains')->where('subdomain', $subdomain)->first();

        if (!$data) {
            return $this->returnErrorResponse('Subdomain not found');
        }

        $logo = ClinicInfo::first()->clinic_image;
        $resObj = (object)[
            'doctor_name' => $data->doctor_name,
            'logo' => $logo
        ];

        return $this->returnSuccessResponse('Subdomain found', $resObj);
    }

    /**
     * Request password reset link.
     */
    public function requestPasswordReset(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return $this->returnErrorResponse('Email not found');
        }

        $token = Str::random(32);
        $user->remember_token = $token;
        $user->save();

        $details = [
            'link' => 'https://' . $request->route('subdomain') . '.ayadty.com/el3yada/api/passwordReset/' . $token
        ];

        Mail::to($request->email)->send(new PasswordResetMail($details));

        return $this->returnSuccessResponse('Password reset link sent', $token);
    }

    /**
     * Reset password using the token.
     */
    public function passwordReset(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|min:6|confirmed'
        ]);

        $user = User::where('email', $request->email)->where('remember_token', $request->token)->first();
        if (!$user) {
            return $this->returnErrorResponse('Invalid token or email');
        }

        $user->remember_token = null;
        $user->password = Hash::make($request->password);
        $user->save();

        return $this->returnSuccessResponse('Password changed successfully');
    }

    /**
     * Request code for password reset.
     */
    public function requestCodeResetPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return $this->returnErrorResponse('Email not found');
        }

        $code = rand(100000, 999999);
        $user->temp_codes = json_encode([$code]);
        $user->save();

        $details = [
            'name' => $user->first_name . ' ' . $user->last_name,
            'email' => $user->email,
            'subject' => 'Reset Password Code',
            'code' => $code
        ];

        Mail::to($user->email)->send(new ResetPasswordCode($details));

        return $this->returnSuccessResponse('Code sent to your email');
    }

    /**
     * Verify the code for password reset.
     */
    public function checkTheCodePasswordReset(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|numeric'
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return $this->returnErrorResponse('Email not found');
        }

        $codes = json_decode($user->temp_codes);
        if (in_array($request->code, $codes)) {
            $token = Str::random(60);
            DB::table('password_resets')->insert(['email' => $request->email, 'token' => $token]);

            $user->temp_codes = null;
            $user->save();

            return $this->returnSuccessResponse('Code verified', $token);
        }

        return $this->returnErrorResponse('Invalid code');
    }

    /**
     * Reset password using the code.
     */
    public function passwordResetWithCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|min:6|confirmed'
        ]);

        $passwordReset = DB::table('password_resets')->where('token', $request->token)->where('email', $request->email)->first();
        if (!$passwordReset) {
            return $this->returnErrorResponse('Invalid token or email');
        }

        User::where('email', $request->email)->update(['password' => Hash::make($request->password)]);
        DB::table('password_resets')->where('token', $request->token)->where('email', $request->email)->delete();

        return $this->returnSuccessResponse('Password changed successfully');
    }

    /**
     * Log out the authenticated user.
     */
    public function logout()
    {
        return "czxzcx";
        Auth::guard('api')->logout();
        return response()->json(['message' => 'Successfully logged out', 'status' => 200]);
    }

    /**
     * Refresh the authentication token.
     */
    public function refresh()
    {
        return $this->respondWithToken(Auth::guard('api')->refresh());
    }

    /**
     * Respond with the token array structure.
     */
    // protected function respondWithToken($token)
    // {
    //     return response()->json([
    //         'status' => 200,
    //         'token' => $token,
    //         'token_type' => 'bearer',
    //         'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
    //         'message' => 'Logged in successfully'
    //     ]);
    // }
}