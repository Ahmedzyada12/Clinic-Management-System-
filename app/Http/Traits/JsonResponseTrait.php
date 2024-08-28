<?php

namespace App\Http\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

trait JsonResponseTrait
{


    public function getAllData($data)
    {
        return response()->json([
            'data' => $data,
        ]);
    }

    public function successResponse($data, $msg = "Saved Successfully", $status = 200)
    {
        return response()->json([
            'data' => $data,
            'message' => $msg,
            'status' => $status
        ]);
    }

    public function updateResponse($data, $msg = "Updated Successfully", $status = 200)
    {
        return response()->json([
            'data' => $data,
            'message' => $msg,
            'status' => $status
        ]);
    }

    public function errorResponse($msg = "Error Occurred", $status = 400)
    {
        return response()->json([
            'message' => $msg,
            'status' => $status
        ]);
    }

    public function validationResponse(Request $request, array $rules, $id = null)
    {


        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $errorMessage = $validator->errors()->first();
            $response = [
                'status'  => 400,
                'message' => $errorMessage,
            ];
            return response()->json($response, 200);
        }

    }

    public function deleteResponse($msg = "Deleted Successfully", $status = 200)
    {
        return response()->json([
            'message' => $msg,
            'status' => $status
        ]);
    }

    public function notFoundResponse($msg = "Data not found", $status = 400)
    {
        return response()->json([
            'message' => $msg,
            'status' => $status
        ]);
    }

}
