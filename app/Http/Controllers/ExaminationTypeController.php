<?php

namespace App\Http\Controllers;

use App\Models\ExaminationType;
use Illuminate\Http\Request;

class ExaminationTypeController extends Controller
{
    public function index(Request $request)
    {
       
        $perPage = $request->input('per_page', 10);

        $examinationTypes = ExaminationType::with('doctor:id,first_name,last_name')->paginate($perPage);
        $examinationTypes->getCollection()->transform(function ($examinationType) {
            return [
                'id' => $examinationType->id,
                'name' => $examinationType->name,
                'amount' => $examinationType->amount,
                'color' => $examinationType->color,
                'doctor' => $examinationType->doctor ? [
                    'id' => $examinationType->doctor->id,
                    'first_name' => $examinationType->doctor->first_name,
                    'last_name' => $examinationType->doctor->last_name,
                ] : null,
            ];
        });

        // Return the paginated and transformed data as a JSON response
        return response()->json([
            'data' => $examinationTypes->items(), // Current page's items
            'current_page' => $examinationTypes->currentPage(),
            'last_page' => $examinationTypes->lastPage(),
            'per_page' => $examinationTypes->perPage(),
            'total' => $examinationTypes->total(),
            'status' => 200
        ]);
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric',
            'color' => 'required|string|max:255',
            'doctor_id' => 'required|exists:users,id'
        ]);

        $examinationType = ExaminationType::create($request->all());

        return response()->json([
            'status' => 200,
            'message' => 'Examination type created successfully',
            'data' => $examinationType
        ], 201);
    }

    public function show($id)
    {
        $examinationType = ExaminationType::find($id);

        if (!$examinationType) {
            return response()->json(['message' => 'Examination type not found'], 404);
        }

        return response()->json($examinationType);
    }
    public function examinationTypeBydoctor($doctor_id)
    {

        $examinationTypes = ExaminationType::where('doctor_id', $doctor_id)->get();

        if ($examinationTypes->isEmpty()) {
            return response()->json(['message' => 'No examination types found for this doctor'], 404);
        }
        return response()->json([
            'data' => $examinationTypes,
            'status' => 200
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'string|max:255',
            'amount' => 'numeric',
            'color' => 'string|max:255',
            'doctor_id' => 'exists:users,id'
        ]);

        $examinationType = ExaminationType::find($id);

        if (!$examinationType) {
            return response()->json(['message' => 'Examination type not found'], 404);
        }

        $examinationType->update($request->all());
        return response()->json([
            'status' => 200,
            'message' => 'Examination type updated successfully',
            'data' => $examinationType
        ]);
    }

    public function destroy($id)
    {
        $examinationType = ExaminationType::find($id);

        if (!$examinationType) {
            return response()->json(['message' => 'Examination type not found'], 404);
        }

        $examinationType->delete();
        return response()->json([
            'status' => 200,
            'message' => 'Examination type deleted successfully'
        ]);
    }
}