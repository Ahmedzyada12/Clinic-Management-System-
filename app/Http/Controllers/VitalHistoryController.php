<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\VitalHistory;
use Illuminate\Http\Request;

class VitalHistoryController extends Controller
{
    public function index()
    {
        try {
            $vitalHistories = VitalHistory::with(['patient', 'doctor'])->get();
            return response()->json([
                'data' => $vitalHistories,
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching the vital histories.',
                'error' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'patient_id' => 'required|exists:patients,id',
                'doctor_id' => "required|exists:users,id",
                'pressure' => 'required|numeric',
                'weight' => 'required|numeric',
                'blood_sugar' => 'required|numeric',
                'report' => 'required|string',
                'date' => 'required|date',
                'patient_show' => 'required|boolean', // Validate patient_show as a boolean
            ]);

            $vitalHistory = VitalHistory::create($validated);

            return response()->json([
                'data' => $vitalHistory,
                'message' => 'Vital history created successfully.',
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
                'message' => 'An error occurred while creating the vital history.',
                'error' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    public function show($id)
    {
        // Retrieve a specific record along with the associated patient
        try {
            $vitalHistory = VitalHistory::with('patient')->findOrFail($id);
            return response()->json([
                'data' => $vitalHistory,
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'The requested record was not found.',
                'error' => $e->getMessage(),
                'status' => 404
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'patient_id' => 'sometimes|required|exists:patients,id',
                'doctor_id' => "required|exists:users,id",
                'pressure' => 'sometimes|required|numeric',
                'weight' => 'sometimes|required|numeric',
                'blood_sugar' => 'sometimes|required|numeric',
                'report' => 'sometimes|required|string',
                'date' => 'sometimes|required|date',
                'patient_show' => 'required|boolean',
            ]);

            $vitalHistory = VitalHistory::findOrFail($id);
            $vitalHistory->update($validated);

            return response()->json([
                'data' => $vitalHistory,
                'message' => 'Record updated successfully.',
                'status' => 200
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation error.',
                'errors' => $e->errors(),
                'status' => 422
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating the record.',
                'error' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $vitalHistory = VitalHistory::findOrFail($id);
            $vitalHistory->delete();

            return response()->json([
                'message' => 'Record deleted successfully.',
                'status' => 204
            ], 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while deleting the record.',
                'error' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    public function VitalhistoryBYpatient($id)
    {
        try {
            $patient = Patient::with(['vitalHistories' => function ($query) {
                $query->where('patient_show', 1);
            }, 'vitalHistories.doctor'])->findOrFail($id);
            return response()->json([
                'data' => $patient,
                'status' => 200
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'The requested record was not found.',
                'error' => $e->getMessage(),
                'status' => 404
            ], 404);
        }
    }
}
