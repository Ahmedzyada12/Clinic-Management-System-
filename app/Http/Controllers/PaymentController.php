<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::all();
        return response()->json(['data' => $payments, 'status' => 200]);
    }

    // Store a newly created payment in storage.
    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'extra_amount' => 'nullable|numeric',
            'total' => 'nullable|numeric',
            'status' => 'nullable|string',
            'comment' => 'nullable|string',
        ]);

        $payment = Payment::create($request->all());
        return response()->json($payment, 201);
    }

    // Display the specified payment.
    public function show(Payment $payment)
    {
        return response()->json($payment);
    }

    // Update the specified payment in storage.
    public function update(Request $request, Payment $payment)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'extra_amount' => 'nullable|numeric',
            'status' => 'nullable|string',
            'comment' => 'nullable|string',
        ]);

        $payment->update($request->all());

        return response()->json($payment);
    }

    // Remove the specified payment from storage.
    public function destroy(Payment $payment)
    {
        $payment->delete();

        return response()->json(null, 204);
    }
}