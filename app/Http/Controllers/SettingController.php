<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $settings = Setting::all();
            return response()->json([
                'message' => 'Settings retrieved successfully',
                'data' => $settings,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An unexpected error occurred while retrieving settings',
                'error' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'doctor_id' => 'required|exists:users,id',
                'api_key_myfatoorah' => 'required|string|unique:settings',
            ]);
            // تحديث السجل إذا كان موجودًا أو إنشاء سجل جديد
            $setting = Setting::updateOrCreate(
                ['doctor_id' => $validated['doctor_id']], // استخدم doctor_id كشرط التحقق
                ['api_key_myfatoorah' => $validated['api_key_myfatoorah']] // القيم المراد تحديثها أو إدخالها
            );

            return response()->json([
                'message' => 'Setting saved successfully',
                'data' => $setting,
                'status' => 200
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation Error',
                'errors' => $e->errors(),
                'status' => 422
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An unexpected error occurred',
                'error' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            // Validate the request data
            $validated = $request->validate([
                'api_key_myfatoorah' => 'required|string|unique:settings,api_key_myfatoorah,' . $id,
            ]);

            // Find the setting record by id
            $setting = Setting::findOrFail($id);

            $setting->update([
                'api_key_myfatoorah' => $validated['api_key_myfatoorah']
            ]);

            return response()->json([
                'message' => 'Setting updated successfully',
                'data' => $setting,
                'status' => 200
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation Error',
                'errors' => $e->errors(),
                'status' => 400
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An unexpected error occurred',
                'error' => $e->getMessage(),
                'status' => 500
            ]);
        }
    }

    public function getSettingsByDoctor($doctor_id)
    {
        try {
            $settings = Setting::where('doctor_id', $doctor_id)->with('doctor')->first();

            return response()->json([
                'message' => 'Settings retrieved successfully',
                'data' => $settings
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An unexpected error occurred while retrieving settings',
                'error' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    public function show($id)
    {
        $setting = Setting::findOrFail($id);
        return response()->json($setting);
    }

    /**
     * Update the specified resource in storage.
     */


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $setting = Setting::findOrFail($id);
        $setting->delete();

        return response()->json([
            'message' => 'Setting deleted successfully'
        ], 204);
    }
}