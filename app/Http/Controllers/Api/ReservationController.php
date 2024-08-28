<?php

namespace App\Http\Controllers\Api;

use App\Models\Payment;
use App\Models\Appointment;
use App\Models\Reservation;
use Illuminate\Http\Request;
use App\Models\ExaminationType;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use GuzzleHttp\Client;

class ReservationController extends Controller
{
    private $rules = [
        'appointment_id' => 'required|exists:appointmentss,id',
        'patient_id' => 'required|exists:patients,id',
        'examination_id' => 'required|exists:examination_types,id',
    ];

    public function index()
    {
        // Fetch reservations with related models
        $reservations = Reservation::with(['patient', 'appointment.examinationType', 'examination'])->get();

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
                'patient' => $reservation->patient ? [
                    'id' => $reservation->patient->id,
                    'first_name' => $reservation->patient->first_name,
                    'last_name' => $reservation->patient->last_name,
                    'email' => $reservation->patient->email,
                    'phone' => $reservation->patient->phone,
                ] : null,
                'doctor' => $reservation->appointment->doctor ? [
                    'id' => $reservation->appointment->doctor->id,
                    'first_name' => $reservation->appointment->doctor->first_name,
                    'last_name' => $reservation->appointment->doctor->last_name,
                ] : null,
                'appointment' => $reservation->appointment ? [
                    'id' => $reservation->appointment->id,
                    'doctor_id' => $reservation->appointment->doctor_id,
                    'time_start' => $reservation->appointment->time_start,
                    'time_end' => $reservation->appointment->time_end,
                    'status' => $reservation->appointment->status,
                ] : null,
                'examination_type' => $reservation->appointment && $reservation->examination ? [
                    'id' => $reservation->examination->id,
                    'name' => $reservation->examination->name,
                    'amount' => $reservation->examination->amount,
                    'color' => $reservation->examination->color,
                ] : null,
                // 'payment' => $reservation->payment ? [
                //     'id' => $reservation->payment->id,
                //     'amount' => $reservation->payment->amount,
                //     'extra_amount' => $reservation->payment->extra_amount,
                //     'total' => $reservation->payment->total,
                //     'status' => $reservation->payment->status,
                //     'comment' => $reservation->payment->comment,
                //     'created_at' => $reservation->payment->created_at,
                //     'updated_at' => $reservation->payment->updated_at,
                // ] : null,
            ];
        });

        return response()->json(['data' => $formattedReservations, 'status' => 200]);
    }
    // public function store(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'patient_id' => 'required|exists:patients,id',
    //         'appointment_id' => 'required|exists:appointmentss,id',
    //         'examination_id' => 'required|exists:examination_types,id',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['status' => 400, 'message' => $validator->errors()->first()]);
    //     }

    //     // Check if the patient already has a reservation on the same day
    //     $existingReservation = Reservation::where('patient_id', $request->patient_id)
    //         ->whereDate('created_at', date('Y-m-d'))
    //         ->where('status', '!=', 'canceled')
    //         ->first();

    //     if ($existingReservation) {
    //         return response()->json(['status' => 400, 'message' => 'You can only book one appointment per day.']);
    //     }

    //     // Check if the appointment is already reserved and not canceled
    //     $existingReservationForAppointment = Reservation::where('appointment_id', $request->appointment_id)
    //         ->where('status', '!=', 'canceled')
    //         ->first();

    //     if ($existingReservationForAppointment) {
    //         return response()->json(['status' => 400, 'message' => 'This appointment has already been reserved.']);
    //     }

    //     // Check if the appointment has a canceled reservation to rebook
    //     $canceledReservation = Reservation::where('appointment_id', $request->appointment_id)
    //         ->where('status', 'canceled')
    //         ->first();

    //     if ($canceledReservation) {
    //         // Update the canceled reservation
    //         $canceledReservation->patient_id = $request->patient_id;
    //         $canceledReservation->examination_id = $request->examination_id;
    //         $canceledReservation->status = 'reserved';
    //         $canceledReservation->save();

    //         // Mark the appointment as reserved
    //         $appointment = Appointment::find($request->appointment_id);
    //         if ($appointment) {
    //             $appointment->status = 0; // Mark the appointment as reserved
    //             $appointment->save();
    //         }

    //         $canceledReservation->load('appointment');
    //         return response()->json(['message' => 'Reservation rebooked successfully', 'data' => $canceledReservation, 'status' => 200]);
    //     }

    //     // Create a new reservation
    //     $reservation = Reservation::create([
    //         'patient_id' => $request->patient_id,
    //         'appointment_id' => $request->appointment_id,
    //         'examination_id' => $request->examination_id,
    //         'status' => 'reserved'
    //     ]);

    //     // Mark the appointment as reserved
    //     $appointment = Appointment::find($request->appointment_id);
    //     if ($appointment) {
    //         $appointment->status = 0; // Mark the appointment as reserved
    //         $appointment->save();
    //     }

    //     $reservation->load('appointment');
    //     return response()->json(['message' => 'Reservation created successfully', 'data' => $reservation, 'status' => 200]);
    // }
    // public function store(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'patient_id' => 'required|exists:patients,id',
    //         'appointment_id' => 'required|exists:appointmentss,id',
    //         'examination_id' => 'required|exists:examination_types,id',
    //         'extra_amount' => 'nullable|numeric',
    //         'status' => 'nullable|string',
    //         'comment' => 'nullable|string',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['status' => 400, 'message' => $validator->errors()->first()]);
    //     }

    //     // Check if the patient already has a reservation on the same day
    //     $existingReservation = Reservation::where('patient_id', $request->patient_id)
    //         ->whereDate('created_at', date('Y-m-d'))
    //         ->where('status', '!=', 'canceled')
    //         ->first();

    //     if ($existingReservation) {
    //         return response()->json(['status' => 400, 'message' => 'You can only book one appointment per day.']);
    //     }

    //     // Check if the appointment is already reserved and not canceled
    //     $existingReservationForAppointment = Reservation::where('appointment_id', $request->appointment_id)
    //         ->where('status', '!=', 'canceled')
    //         ->first();

    //     if ($existingReservationForAppointment) {
    //         return response()->json(['status' => 400, 'message' => 'This appointment has already been reserved.']);
    //     }

    //     // Check if the appointment has a canceled reservation to rebook
    //     $canceledReservation = Reservation::where('appointment_id', $request->appointment_id)
    //         ->where('status', 'canceled')
    //         ->first();

    //     // Get the amount from the examination table
    //     $examination = ExaminationType::find($request->examination_id);
    //     if (!$examination) {
    //         return response()->json(['status' => 400, 'message' => 'Invalid examination ID.']);
    //     }
    //     $amount = $examination->amount;

    //     if ($canceledReservation) {
    //         // Update the canceled reservation
    //         $canceledReservation->patient_id = $request->patient_id;
    //         $canceledReservation->examination_id = $request->examination_id;
    //         $canceledReservation->status = 'reserved';
    //         $canceledReservation->save();

    //         // Mark the appointment as reserved
    //         $appointment = Appointment::find($request->appointment_id);
    //         if ($appointment) {
    //             $appointment->status = 0; // Mark the appointment as reserved
    //             $appointment->save();
    //         }

    //         $canceledReservation->load('appointment');
    //         // Create a payment
    //         $payment = Payment::create([
    //             'amount' => $amount,
    //             'extra_amount' => $request->extra_amount,
    //             'total' => $amount + $request->extra_amount,
    //             'status' => 'pending',
    //             'comment' => $request->comment,
    //         ]);

    //         return response()->json([
    //             'message' => 'Reservation rebooked and payment created successfully',
    //             'reservation' => $canceledReservation,
    //             'payment' => $payment,
    //             'status' => 200
    //         ]);
    //     }

    //     // Create a new reservation
    //     $reservation = Reservation::create([
    //         'patient_id' => $request->patient_id,
    //         'appointment_id' => $request->appointment_id,
    //         'examination_id' => $request->examination_id,
    //         'status' => 'reserved'
    //     ]);

    //     // Mark the appointment as reserved
    //     $appointment = Appointment::find($request->appointment_id);
    //     if ($appointment) {
    //         $appointment->status = 0; // Mark the appointment as reserved
    //         $appointment->save();
    //     }

    //     $reservation->load('appointment');

    //     // Create a payment
    //     $payment = Payment::create([
    //         'amount' => $amount,
    //         'extra_amount' => $request->extra_amount,
    //         'total' => $amount + $request->extra_amount,
    //         'status' => 'pending',
    //         'comment' => $request->comment,
    //     ]);

    //     return response()->json([
    //         'message' => 'Reservation and payment created successfully',
    //         'reservation' => $reservation,
    //         'payment' => $payment,
    //         'status' => 200
    //     ]);
    // }
    // public function store(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'patient_id' => 'required|exists:patients,id',
    //         'appointment_id' => 'required|exists:appointmentss,id',
    //         'examination_id' => 'required|exists:examination_types,id',
    //         'extra_amount' => 'nullable|numeric',
    //         'status' => 'nullable|string',
    //         'comment' => 'nullable|string',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['status' => 400, 'message' => $validator->errors()->first()]);
    //     }
    //     $examination = ExaminationType::find($request->examination_id);

    //     if (!$examination) {
    //         return response()->json(['status' => 400, 'message' => 'Examination type not found.']);
    //     }

    //     // التحقق مما إذا كان هناك حجز موجود مع نفس الطبيب في نفس اليوم
    //     $existingReservation = Reservation::where('patient_id', $request->patient_id)
    //         ->whereDate('created_at', date('Y-m-d'))
    //         ->where('status', '!=', 'canceled')
    //         ->whereHas('examination_type', function ($query) use ($examination) {
    //             $query->where('doctor_id', $examination->doctor_id);
    //         })
    //         ->first();
    //     if ($existingReservation) {
    //         return response()->json(['status' => 400, 'message' => 'You can only book one appointment with the same doctor per day.']);
    //     }

    //     // Check if the appointment is already reserved and not canceled
    //     $existingReservationForAppointment = Reservation::where('appointment_id', $request->appointment_id)
    //         ->where('status', '!=', 'canceled')
    //         ->first();

    //     if ($existingReservationForAppointment) {
    //         return response()->json(['status' => 400, 'message' => 'This appointment has already been reserved.']);
    //     }

    //     // Check if the appointment has a canceled reservation to rebook
    //     $canceledReservation = Reservation::where('appointment_id', $request->appointment_id)
    //         ->where('status', 'canceled')
    //         ->first();

    //     // Get the amount from the examination table
    //     $examination = ExaminationType::find($request->examination_id);
    //     if (!$examination) {
    //         return response()->json(['status' => 400, 'message' => 'Invalid examination ID.']);
    //     }
    //     $amount = $examination->amount;

    //     if ($canceledReservation) {
    //         // Update the canceled reservation
    //         $canceledReservation->patient_id = $request->patient_id;
    //         $canceledReservation->examination_id = $request->examination_id;
    //         $canceledReservation->status = 'reserved';
    //         $canceledReservation->save();

    //         // Mark the appointment as reserved
    //         $appointment = Appointment::find($request->appointment_id);
    //         if ($appointment) {
    //             $appointment->status = 0; // Mark the appointment as reserved
    //             $appointment->save();
    //         }

    //         $canceledReservation->load('appointment');
    //         // Create a payment
    //         $payment = Payment::create([
    //             'amount' => $amount,
    //             'extra_amount' => $request->extra_amount,
    //             'total' => $amount + $request->extra_amount,
    //             'status' => 'pending',
    //             'comment' => $request->comment,
    //             'reservation_id' => $canceledReservation->id,
    //         ]);

    //         return response()->json([
    //             'message' => 'Reservation rebooked and payment created successfully',
    //             'reservation' => $canceledReservation,
    //             'payment' => $payment,
    //             'status' => 200
    //         ]);
    //     }

    //     // Create a new reservation
    //     $reservation = Reservation::create([
    //         'patient_id' => $request->patient_id,
    //         'appointment_id' => $request->appointment_id,
    //         'examination_id' => $request->examination_id,
    //         'status' => 'reserved'
    //     ]);

    //     // Mark the appointment as reserved
    //     $appointment = Appointment::find($request->appointment_id);
    //     if ($appointment) {
    //         $appointment->status = 0; // Mark the appointment as reserved
    //         $appointment->save();
    //     }

    //     $reservation->load('appointment');

    //     // Create a payment
    //     $payment = Payment::create([
    //         'amount' => $amount,
    //         'extra_amount' => $request->extra_amount,
    //         'total' => $amount + $request->extra_amount,
    //         'status' => 'pending',
    //         'comment' => $request->comment,
    //         'reservation_id' => $reservation->id,
    //     ]);

    //     return response()->json([
    //         'message' => 'Reservation and payment created successfully',
    //         'reservation' => $reservation,
    //         'payment' => $payment,
    //         'status' => 200
    //     ]);
    // }11111
    // public function store(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'patient_id' => 'required|exists:patients,id',
    //         'appointment_id' => 'required|exists:appointmentss,id',
    //         'examination_id' => 'required|exists:examination_types,id',
    //         'extra_amount' => 'nullable|numeric',
    //         'status' => 'nullable|string',
    //         'comment' => 'nullable|string',
    //         'payment_method' => 'required|string|in:cash,visa', // Adding validation for payment_method
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['status' => 400, 'message' => $validator->errors()->first()]);
    //     }

    //     $examination = ExaminationType::find($request->examination_id);

    //     if (!$examination) {
    //         return response()->json(['status' => 400, 'message' => 'Examination type not found.']);
    //     }

    //     // Check for an existing reservation with the same doctor on the same day
    //     $existingReservation = Reservation::where('patient_id', $request->patient_id)
    //         ->whereDate('created_at', date('Y-m-d'))
    //         ->where('status', '!=', 'canceled')
    //         ->whereHas('appointment', function ($query) use ($examination) {
    //             $query->where('doctor_id', $examination->doctor_id);
    //         })
    //         ->first();

    //     if ($existingReservation) {
    //         return response()->json(['status' => 400, 'message' => 'You can only book one appointment with the same doctor per day.']);
    //     }

    //     // Check if the appointment is already reserved and not canceled
    //     $existingReservationForAppointment = Reservation::where('appointment_id', $request->appointment_id)
    //         ->where('status', '!=', 'canceled')
    //         ->first();

    //     if ($existingReservationForAppointment) {
    //         return response()->json(['status' => 400, 'message' => 'This appointment has already been reserved.']);
    //     }

    //     // Check if the appointment has a canceled reservation to rebook
    //     $canceledReservation = Reservation::where('appointment_id', $request->appointment_id)
    //         ->where('status', 'canceled')
    //         ->first();

    //     $amount = $examination->amount;

    //     if ($canceledReservation) {
    //         // Update the canceled reservation
    //         $canceledReservation->patient_id = $request->patient_id;
    //         $canceledReservation->examination_id = $request->examination_id;
    //         $canceledReservation->status = 'reserved';
    //         $canceledReservation->save();

    //         // Mark the appointment as reserved
    //         $appointment = Appointment::find($request->appointment_id);
    //         if ($appointment) {
    //             $appointment->status = 0; // Mark the appointment as reserved
    //             $appointment->save();
    //         }

    //         $canceledReservation->load('appointment');

    //         // Create a payment
    //         $payment = Payment::create([
    //             'amount' => $amount,
    //             'extra_amount' => $request->extra_amount,
    //             'total' => $amount + $request->extra_amount,
    //             'status' => 'pending',
    //             'comment' => $request->comment,
    //             'reservation_id' => $canceledReservation->id,
    //             'payment_method' => $request->payment_method, // Include payment_method here
    //         ]);

    //         return response()->json([
    //             'message' => 'Reservation rebooked and payment created successfully',
    //             'reservation' => $canceledReservation,
    //             'payment' => $payment,
    //             'status' => 200
    //         ]);
    //     }

    //     // Create a new reservation
    //     $reservation = Reservation::create([
    //         'patient_id' => $request->patient_id,
    //         'appointment_id' => $request->appointment_id,
    //         'examination_id' => $request->examination_id,
    //         'status' => 'reserved'
    //     ]);

    //     // Mark the appointment as reserved
    //     $appointment = Appointment::find($request->appointment_id);
    //     if ($appointment) {
    //         $appointment->status = 0; // Mark the appointment as reserved
    //         $appointment->save();
    //     }

    //     $reservation->load('appointment');

    //     // Create a payment
    //     $payment = Payment::create([
    //         'amount' => $amount,
    //         'extra_amount' => $request->extra_amount,
    //         'total' => $amount + $request->extra_amount,
    //         'status' => 'pending',
    //         'comment' => $request->comment,
    //         'reservation_id' => $reservation->id,
    //         'payment_method' => $request->payment_method, // Include payment_method here
    //     ]);

    //     return response()->json([
    //         'message' => 'Reservation and payment created successfully',
    //         'reservation' => $reservation,
    //         'payment' => $payment,
    //         'status' => 200
    //     ]);
    // }2222


    // public function store(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'patient_id' => 'required|exists:patients,id',
    //         'appointment_id' => 'required|exists:appointmentss,id',
    //         'examination_id' => 'required|exists:examination_types,id',
    //         'extra_amount' => 'nullable|numeric',
    //         'status' => 'nullable|string',
    //         'comment' => 'nullable|string',
    //         'payment_method' => 'required|string|in:cash,visa', // Adding validation for payment_method
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['status' => 400, 'message' => $validator->errors()->first()]);
    //     }

    //     $examination = ExaminationType::find($request->examination_id);

    //     if (!$examination) {
    //         return response()->json(['status' => 400, 'message' => 'Examination type not found.']);
    //     }

    //     // Check for an existing reservation with the same doctor on the same day
    //     $existingReservation = Reservation::where('patient_id', $request->patient_id)
    //         ->whereDate('created_at', date('Y-m-d'))
    //         ->where('status', '!=', 'canceled')
    //         ->whereHas('appointment', function ($query) use ($examination) {
    //             $query->where('doctor_id', $examination->doctor_id);
    //         })
    //         ->first();

    //     if ($existingReservation) {
    //         return response()->json(['status' => 400, 'message' => 'You can only book one appointment with the same doctor per day.']);
    //     }

    //     // Check if the appointment is already reserved and not canceled
    //     $existingReservationForAppointment = Reservation::where('appointment_id', $request->appointment_id)
    //         ->where('status', '!=', 'canceled')
    //         ->first();

    //     if ($existingReservationForAppointment) {
    //         return response()->json(['status' => 400, 'message' => 'This appointment has already been reserved.']);
    //     }

    //     // Check if the appointment has a canceled reservation to rebook
    //     $canceledReservation = Reservation::where('appointment_id', $request->appointment_id)
    //         ->where('status', 'canceled')
    //         ->first();

    //     $amount = $examination->amount;

    //     if ($canceledReservation) {
    //         // Update the canceled reservation
    //         $canceledReservation->patient_id = $request->patient_id;
    //         $canceledReservation->examination_id = $request->examination_id;
    //         $canceledReservation->status = 'reserved';
    //         $canceledReservation->save();

    //         // Mark the appointment as reserved
    //         $appointment = Appointment::find($request->appointment_id);
    //         if ($appointment) {
    //             $appointment->status = 0; // Mark the appointment as reserved
    //             $appointment->save();
    //         }

    //         $canceledReservation->load('appointment');

    //         // Handle Payment Based on Payment Method
    //         if ($request->payment_method === 'visa') {
    //             return $this->handleVisaPayment($canceledReservation, $amount, $request);
    //         }

    //         // For cash payment, complete the payment process immediately
    //         $payment = Payment::create([
    //             'amount' => $amount,
    //             'extra_amount' => $request->extra_amount,
    //             'total' => $amount + $request->extra_amount,
    //             'status' => 'completed',
    //             'comment' => $request->comment,
    //             'reservation_id' => $canceledReservation->id,
    //             'payment_method' => 'cash',
    //         ]);

    //         return response()->json([
    //             'message' => 'Reservation rebooked and payment created successfully',
    //             'reservation' => $canceledReservation,
    //             'payment' => $payment,
    //             'status' => 200
    //         ]);
    //     }

    //     // Create a new reservation
    //     $reservation = Reservation::create([
    //         'patient_id' => $request->patient_id,
    //         'appointment_id' => $request->appointment_id,
    //         'examination_id' => $request->examination_id,
    //         'status' => 'reserved'
    //     ]);

    //     // Mark the appointment as reserved
    //     $appointment = Appointment::find($request->appointment_id);
    //     if ($appointment) {
    //         $appointment->status = 0; // Mark the appointment as reserved
    //         $appointment->save();
    //     }

    //     $reservation->load('appointment');

    //     // Handle Payment Based on Payment Method
    //     if ($request->payment_method === 'visa') {
    //         return $this->handleVisaPayment($reservation, $amount, $request);
    //     }

    //     // For cash payment, complete the payment process immediately
    //     $payment = Payment::create([
    //         'amount' => $amount,
    //         'extra_amount' => $request->extra_amount,
    //         'total' => $amount + $request->extra_amount,
    //         'status' => 'completed',
    //         'comment' => $request->comment,
    //         'reservation_id' => $reservation->id,
    //         'payment_method' => 'cash',
    //     ]);

    //     return response()->json([
    //         'message' => 'Reservation and payment created successfully',
    //         'reservation' => $reservation,
    //         'payment' => $payment,
    //         'status' => 200
    //     ]);
    // }

    // public function store(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'patient_id' => 'required|exists:patients,id',
    //         'appointment_id' => 'required|exists:appointmentss,id',
    //         'examination_id' => 'required|exists:examination_types,id',
    //         'extra_amount' => 'nullable|numeric',
    //         'status' => 'nullable|string',
    //         'comment' => 'nullable|string',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['status' => 400, 'message' => $validator->errors()->first()]);
    //     }

    //     // تحقق مما إذا كان الموعد صالح
    //     $appointment = Appointment::find($request->appointment_id);
    //     if (!$appointment) {
    //         return response()->json(['status' => 400, 'message' => 'Invalid appointment ID.']);
    //     }

    //     $existingReservation = Reservation::where('patient_id', $request->patient_id)
    //         ->whereHas('appointment', function ($query) use ($appointment) {
    //             $query->where('doctor_id', $appointment->doctor_id)
    //                 ->whereDate('time_start', $appointment->time_start);
    //         })
    //         ->where('status', '!=', 'canceled')
    //         ->first();

    //     if ($existingReservation) {
    //         return response()->json(['status' => 400, 'message' => 'You can only book one appointment per day with the same doctor.']);
    //     }

    //     $existingReservationForAppointment = Reservation::where('appointment_id', $request->appointment_id)
    //         ->where('status', '!=', 'canceled')
    //         ->first();

    //     if ($existingReservationForAppointment) {
    //         return response()->json(['status' => 400, 'message' => 'This appointment has already been reserved.']);
    //     }

    //     // تحقق مما إذا كان هناك حجز ملغى لإعادة الحجز
    //     $canceledReservation = Reservation::where('appointment_id', $request->appointment_id)
    //         ->where('status', 'canceled')
    //         ->first();

    //     // الحصول على قيمة الفحص من جدول الفحص
    //     $examination = ExaminationType::find($request->examination_id);
    //     if (!$examination) {
    //         return response()->json(['status' => 400, 'message' => 'Invalid examination ID.']);
    //     }
    //     $amount = $examination->amount;

    //     if ($canceledReservation) {
    //         // تحديث الحجز الملغى
    //         $canceledReservation->patient_id = $request->patient_id;
    //         $canceledReservation->examination_id = $request->examination_id;
    //         $canceledReservation->status = 'reserved';
    //         $canceledReservation->save();

    //         // تعيين الموعد كمحجوز
    //         $appointment->status = 0; // تعيين حالة الموعد كمحجوز
    //         $appointment->save();

    //         $canceledReservation->load('appointment');
    //         // إنشاء الدفع
    //         $payment = Payment::create([
    //             'amount' => $amount,
    //             'extra_amount' => $request->extra_amount,
    //             'total' => $amount + $request->extra_amount,
    //             'status' => 'pending',
    //             'comment' => $request->comment,
    //             'reservation_id' => $canceledReservation->id,
    //         ]);

    //         return response()->json([
    //             'message' => 'Reservation rebooked and payment created successfully',
    //             'reservation' => $canceledReservation,
    //             'payment' => $payment,
    //             'status' => 200
    //         ]);
    //     }

    //     // إنشاء حجز جديد
    //     $reservation = Reservation::create([
    //         'patient_id' => $request->patient_id,
    //         'appointment_id' => $request->appointment_id,
    //         'examination_id' => $request->examination_id,
    //         'status' => 'reserved'
    //     ]);

    //     // تعيين الموعد كمحجوز
    //     $appointment->status = 0; // تعيين حالة الموعد كمحجوز
    //     $appointment->save();

    //     $reservation->load('appointment');

    //     // إنشاء الدفع
    //     $payment = Payment::create([
    //         'amount' => $amount,
    //         'extra_amount' => $request->extra_amount,
    //         'total' => $amount + $request->extra_amount,
    //         'status' => 'pending',
    //         'comment' => $request->comment,
    //         'reservation_id' => $reservation->id,
    //     ]);

    //     return response()->json([
    //         'message' => 'Reservation and payment created successfully',
    //         'reservation' => $reservation,
    //         'payment' => $payment,
    //         'status' => 200
    //     ]);
    // }
    
    // New method for confirming reservation and payment
    public function confirm(Request $request, $reservationId)
    {
        // Validation including the discount field
        $validator = Validator::make($request->all(), [
            'extra_amount' => 'nullable|numeric',
            'discount' => 'nullable|numeric|min:0', // New discount field
            'status' => 'nullable|string',
            'comment' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'message' => $validator->errors()->first()]);
        }

        $reservation = Reservation::find($reservationId);
        if (!$reservation) {
            return response()->json(['status' => 400, 'message' => 'Invalid reservation ID.']);
        }

        // Check if the reservation is already confirmed
        if ($reservation->status == 'confirmed') {
            return response()->json(['status' => 400, 'message' => 'Reservation is already confirmed.']);
        }

        // Check if the reservation status is 'reserved'
        if ($reservation->status == 'reserved') {
            // Update reservation status to confirmed
            $reservation->status = 'confirmed';
            $reservation->save();

            // Update appointment status to not available
            $appointment = $reservation->appointment;
            if ($appointment) {
                $appointment->status = 'not-available';
                $appointment->save();
            }
            // Update payment details
            $payment = $reservation->payment;
            if ($payment) {
                $extraAmount = $request->extra_amount ?? 0;
                $discount = $request->discount ?? 0;
                $totalAmount = $payment->amount + $extraAmount - $discount;

                $payment->extra_amount = $extraAmount;
                $payment->discount = $discount; // Assume discount field exists in the payments table
                $payment->total = $totalAmount > 0 ? $totalAmount : 0; // Ensure total doesn't go below zero
                $payment->status = 'confirmed';
                $payment->comment = $request->comment;
                $payment->save();
            }

            return response()->json([
                'message' => 'Reservation and payment confirmed successfully',
                'reservation' => $reservation,
                'payment' => $payment,
                'status' => 200
            ]);
        } else {
            return response()->json(['status' => 400, 'message' => 'Reservation is not in reserved status.']);
        }
    }

    public function getReservationsByDoctor($doctor_id)
    {

        $reservations = Reservation::whereHas('appointment', function ($query) use ($doctor_id) {
            $query->where('doctor_id', $doctor_id);
        })->with(['patient', 'appointment.examinationType', 'examination', 'appointment.doctor:id,first_name,last_name'])->get();

        // if ($reservations->isEmpty()) {
        //     return response()->json(['status' => 404, 'message' => 'No reservations found for the specified doctor.']);
        // }

        $formattedReservations = $reservations->map(function ($reservation) {
            return [
                'id' => $reservation->id,
                'patient_id' => $reservation->patient_id,
                'status' => $reservation->status,
                'created_at' => $reservation->created_at,
                'updated_at' => $reservation->updated_at,
                'examination_id' => $reservation->examination_id,
                'appointment_id' => $reservation->appointment_id,
                'patient' => $reservation->patient ? [
                    'id' => $reservation->patient->id,
                    'first_name' => $reservation->patient->first_name,
                    'last_name' => $reservation->patient->last_name,
                    'email' => $reservation->patient->email,
                    'phone' => $reservation->patient->phone,
                ] : null,
                'appointment' => $reservation->appointment ? [
                    'id' => $reservation->appointment->id,
                    'doctor_id' => $reservation->appointment->doctor_id,
                    'time_start' => $reservation->appointment->time_start,
                    'time_end' => $reservation->appointment->time_end,
                    'status' => $reservation->appointment->status,

                ] : null,
                'doctor' => $reservation->appointment->doctor ? [
                    'id' => $reservation->appointment->doctor->id,
                    'first_name' => $reservation->appointment->doctor->first_name,
                    'last_name' => $reservation->appointment->doctor->last_name,
                ] : null,
                'examination_type' => $reservation->appointment && $reservation->examination ? [
                    'id' => $reservation->examination->id,
                    'name' => $reservation->examination->name,
                    'amount' => $reservation->examination->amount,
                    'color' => $reservation->examination->color,
                ] : null,
            ];
        });

        return response()->json(['status' => 200, 'data' => $formattedReservations], 200);
    }

    public function cancelReservation($reservation_id)
    {
        $reservation = Reservation::find($reservation_id);

        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found'], 404);
        }

        if ($reservation->status === 'canceled') {
            return response()->json(['status' => 200, 'message' => 'The reservation is already canceled and the appointment is available.']);
        }
        // Check if the reservation is already confirmed
        if ($reservation->status == 'confirmed') {
            return response()->json(['status' => 400, 'message' => 'Cannot cancel a confirmed reservation.']);
        }

        // Update the reservation status to 'canceled'
        $reservation->status = 'canceled';
        $reservation->save();

        // Update the related appointment status to 'available' (1)
        $appointment = Appointment::find($reservation->appointment_id);
        if ($appointment) {
            $appointment->status = 'available'; // Mark the appointment as available
            $appointment->save();
        }

        return response()->json([
            'status' => 200,
            'message' => 'Reservation canceled successfully',
            'data' => $reservation
        ]);
    }

    public function show($id)
    {
        $reservation = Reservation::with(['appointment'])->find($id);

        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found', 'status' => 404]);
        }

        return response()->json(['data' => $reservation, 'status' => 200]);
    }

    public function update(Request $request, $id)
    {
        $rules = [
            'appointment_id' => 'required|exists:appointmentss,id',
            'patient_id' => 'required|exists:patients,id',
            'examination_id' => 'required|exists:examination_types,id',
            'status' => 'required|string'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'message' => $validator->errors()->first()]);
        }

        $reservation = Reservation::find($id);
        if (!$reservation) {
            return response()->json(['status' => 404, 'message' => 'Reservation not found']);
        }

        $reservation->update([
            'patient_id' => $request->patient_id,
            'appointment_id' => $request->appointment_id,
            'examination_id' => $request->examination_id,
            'status' => $request->status,
        ]);

        return response()->json(['message' => 'Reservation updated successfully', 'data' => $reservation, 'status' => 200]);
    }

    public function destroy($id)
    {
        $reservation = Reservation::find($id);

        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found', 'status' => 404]);
        }

        $reservation->delete();
        return response()->json(['message' => 'Reservation deleted successfully', 'status' => 200]);
    }
}