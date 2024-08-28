<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\JsonResponseTrait;
use App\Models\Specialization;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class SpecializationController extends Controller
{

    use JsonResponseTrait;

    public function index()
    {
        $specializations = Specialization::all();

        if ($specializations->isEmpty()) {
            return response()->json(['message' => 'No specializations found.', 'status' => 400]);
        }

        // Return JSON response with paginated specializations
        return response()->json([
            'data' =>   $specializations,
            "status" => 200
        ]);
    }
    public function store(Request $request)
    {
        // Validate the incoming request data
        $validated = $request->validate([
            'name' => 'required'
        ]);

        // Check if the specialization name already exists
        $existingSpecialization = Specialization::where('name', $validated['name'])->first();

        if ($existingSpecialization) {
            // If specialization name already exists, return a message
            return response()->json(['message' => 'Specialization name already exists.', 'status' => 400]);
        }

        // Create a new specialization using the validated data
        $data = Specialization::create($validated);

        // Return a JSON response with the created specialization data
        return response()->json(['data' => $data, 'message' => 'Saved Successfully', 'status' => 200]);
    }


    public function show($id)
    {
        $specialization = Specialization::find($id);
        if (!$specialization) {
            return response()->json(['message' => 'Specialization not found.', 'status' => 400]);
        }
        return response()->json(["data" => $specialization, 'status' => 200]);
    }


    public function destroy($id)
    {
        $specialization = Specialization::find($id);

        if (!$specialization) {
            return response()->json(['message' => 'Specialization not found.', 'status' => 400]);
        }

        // Check if the specialization is associated with any doctor
        if ($specialization->doctors()->count() > 0) {
            return response()->json(['message' => 'Specialization cannot be deleted because it is associated with one or more doctors.', 'status' => 400]);
        }

        $specialization->delete();
        return response()->json(['message' => 'Specialization deleted successfully.', 'status' => 200]);
    }


    public function update(Request $request, $id)
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:specializations,name,' . $id,
        ]);

        if ($validator->fails()) {
            $errorMessage = $validator->errors()->first();
            $response = [
                'status'  => 400,
                'message' => $errorMessage,
            ];
            return response()->json($response, 200);
        }

        // Find the specialization by ID
        $specialization = Specialization::find($id);
        if (!$specialization) {
            return response()->json(['message' => 'Specialization not found.', 'status' => 400]);
        }

        // Update the specialization with validated data
        $specialization->update([
            'name' => $request->input('name'), // Only update the 'name' field
        ]);

        // Return a JSON response with success message and updated data
        return response()->json(['message' => 'Specialization updated successfully.', 'data' => $specialization, 'status' => 200]);
    }
}
