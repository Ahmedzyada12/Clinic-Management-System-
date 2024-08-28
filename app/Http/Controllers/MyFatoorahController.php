<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Appointment;
use App\Models\Reservation;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\ExaminationType;
use MyFatoorah\Library\MyFatoorah;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use MyFatoorah\Library\API\Payment\MyFatoorahPayment;
use MyFatoorah\Library\API\Payment\MyFatoorahPaymentStatus;
use MyFatoorah\Library\API\Payment\MyFatoorahPaymentEmbedded;

class MyFatoorahController extends Controller
{

    /**
     * @var array
     */
    public $mfObj;
    public $mfConfig = [];

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Initiate MyFatoorah Configuration
     */
    // private function initializeConfig(int $doctorId): void
    // {
    //     $setting = Setting::where('doctor_id', $doctorId)->firstOrFail();

    //     $this->mfConfig = [
    //         'apiKey'      => $setting->api_key_myfatoorah,
    //         'isTest'      => false,
    //         'countryCode' => config('myfatoorah.country_iso'),
    //     ];

    //     if (empty($this->mfConfig['apiKey']) || !is_string($this->mfConfig['apiKey'])) {
    //         throw new \Exception('Invalid API key configuration.');
    //     }

    //     // Debugging output
    //     logger()->info('MyFatoorah Config: ', $this->mfConfig);
    // }

    // public function initiatePayment(Request $request)
    // {
    //     try {
    //         // Validate the request
    //         $validator = Validator::make($request->all(), [
    //             'patient_id' => 'required|exists:patients,id',
    //             'appointment_id' => 'required|exists:appointments,id',
    //             'examination_id' => 'required|exists:examination_types,id',
    //             'extra_amount' => 'nullable|numeric',
    //             'status' => 'nullable|string',
    //             'comment' => 'nullable|string',
    //             'payment_method' => 'required|string',
    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json(['status' => 400, 'message' => $validator->errors()->first()]);
    //         }

    //         // Check if the examination exists
    //         $examination = ExaminationType::findOrFail($request->examination_id);
    //         // Check if the appointment exists
    //         $appointment = Appointment::findOrFail($request->appointment_id);

    //         // Initialize MyFatoorah configuration using the doctor's ID
    //         $this->initializeConfig($appointment->doctor_id);

    //         // Check if the patient already has a reservation with the same doctor on the same day
    //         $existingReservation = Reservation::where('patient_id', $request->patient_id)
    //             ->whereHas('appointment', function ($query) use ($appointment) {
    //                 $query->where('doctor_id', $appointment->doctor_id)
    //                     ->whereDate('time_start', '=', date('Y-m-d', strtotime($appointment->time_start)));
    //             })
    //             ->where('status', '!=', 'canceled')
    //             ->first();

    //         if ($existingReservation) {
    //             return response()->json(['status' => 400, 'message' => 'You already have a reservation with this doctor on the same day.']);
    //         }

    //         // Check if the appointment is already reserved and not canceled
    //         $existingReservationForAppointment = Reservation::where('appointment_id', $request->appointment_id)
    //             ->where('status', '!=', 'canceled')
    //             ->first();

    //         if ($existingReservationForAppointment) {
    //             return response()->json(['status' => 400, 'message' => 'This appointment has already been reserved.']);
    //         }

    //         // Get the amount from the examination table
    //         $amount = $examination->amount;

    //         if ($request->payment_method === 'cash') {
    //             // Create reservation with status 'reserved' for cash payment
    //             $reservation = Reservation::create([
    //                 'patient_id' => $request->patient_id,
    //                 'appointment_id' => $request->appointment_id,
    //                 'examination_id' => $request->examination_id,
    //                 'status' => 'reserved'
    //             ]);

    //             $appointment->status = 'not-available'; // Mark the appointment as reserved
    //             $appointment->save();

    //             $payment = Payment::create([
    //                 'amount' => $amount,
    //                 'extra_amount' => $request->extra_amount,
    //                 'total' => $amount + $request->extra_amount,
    //                 'status' => 'pending',
    //                 'comment' => $request->comment,
    //                 'reservation_id' => $reservation->id,
    //                 'payment_method' => $request->payment_method, // Store the payment method
    //             ]);

    //             return response()->json([
    //                 'message' => 'Reservation and cash payment recorded successfully',
    //                 'reservation' => $reservation,
    //                 'payment' => $payment,
    //                 'status' => 200
    //             ]);
    //         } else {
    //             // Proceed with payment processing using MyFatoorah
    //             $curlData = $this->getPayLoadData($request, $examination->amount);
    //             $mfObj = new MyFatoorahPayment($this->mfConfig);

    //             // Attempt to get invoice URL
    //             $data = $mfObj->getInvoiceURL($curlData, 0);

    //             // Check if the response contains an invoice URL
    //             if (isset($data['invoiceURL'])) {
    //                 return response()->json([
    //                     'status' => 200,
    //                     'message' => 'Payment URL generated successfully',
    //                     'payment_url' => $data['invoiceURL'],
    //                 ]);
    //             } else {
    //                 Log::error('MyFatoorah API Error: ' . json_encode($data));
    //                 return response()->json([
    //                     'status' => 500,
    //                     'message' => 'Failed to generate payment URL. Please review MyFatoorah configuration.'
    //                 ]);
    //             }
    //         }
    //     } catch (Exception $e) {
    //         Log::error('Payment initiation error: ' . $e->getMessage());
    //         return response()->json([
    //             'status' => 500,
    //             'message' => 'An error occurred while initiating payment. Please try again later.'
    //         ]);
    //     }
    // }
    // public function initiatePayment(Request $request)
    // {
    //     try {
    //         // Validate the request
    //         $validator = Validator::make($request->all(), [
    //             'patient_id' => 'required|exists:patients,id',
    //             'appointment_id' => 'required|exists:appointments,id',
    //             'examination_id' => 'required|exists:examination_types,id',
    //             'extra_amount' => 'nullable|numeric',
    //             'status' => 'nullable|string',
    //             'comment' => 'nullable|string',
    //             'payment_method' => 'required|string',
    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json(['status' => 400, 'message' => $validator->errors()->first()]);
    //         }

    //         // Check if the examination exists
    //         $examination = ExaminationType::findOrFail($request->examination_id);
    //         // Check if the appointment exists
    //         $appointment = Appointment::findOrFail($request->appointment_id);

    //         // Initialize MyFatoorah configuration using the doctor's ID
    //         $this->initializeConfig($appointment->doctor_id);

    //         // Check if the patient already has a reservation with the same doctor on the same day
    //         $existingReservation = Reservation::where('patient_id', $request->patient_id)
    //             ->whereHas('appointment', function ($query) use ($appointment) {
    //                 $query->where('doctor_id', $appointment->doctor_id)
    //                     ->whereDate('time_start', '=', date('Y-m-d', strtotime($appointment->time_start)));
    //             })
    //             ->where('status', '!=', 'canceled')
    //             ->first();

    //         if ($existingReservation) {
    //             return response()->json(['status' => 400, 'message' => 'You already have a reservation with this doctor on the same day.']);
    //         }

    //         // Check if the appointment is already reserved and not canceled
    //         $existingReservationForAppointment = Reservation::where('appointment_id', $request->appointment_id)
    //             ->where('status', '!=', 'canceled')
    //             ->first();

    //         if ($existingReservationForAppointment) {
    //             return response()->json(['status' => 400, 'message' => 'This appointment has already been reserved.']);
    //         }

    //         // Get the amount from the examination table
    //         $amount = $examination->amount;

    //         if ($request->payment_method === 'cash') {
    //             // Create reservation with status 'reserved' for cash payment
    //             $reservation = Reservation::create([
    //                 'patient_id' => $request->patient_id,
    //                 'appointment_id' => $request->appointment_id,
    //                 'examination_id' => $request->examination_id,
    //                 'status' => 'reserved'
    //             ]);

    //             $appointment->status = 'not-available'; // Mark the appointment as reserved
    //             $appointment->save();

    //             $payment = Payment::create([
    //                 'amount' => $amount,
    //                 'extra_amount' => $request->extra_amount,
    //                 'total' => $amount + $request->extra_amount,
    //                 'status' => 'pending',
    //                 'comment' => $request->comment,
    //                 'reservation_id' => $reservation->id,
    //                 'payment_method' => $request->payment_method, // Store the payment method
    //             ]);

    //             return response()->json([
    //                 'message' => 'Reservation and cash payment recorded successfully',
    //                 'reservation' => $reservation,
    //                 'payment' => $payment,
    //                 'status' => 200
    //             ]);
    //         } else {
    //             // Proceed with payment processing using MyFatoorah
    //             $curlData = $this->getPayLoadData($request, $examination->amount);
    //             $mfObj = new MyFatoorahPayment($this->mfConfig);
    //             $data = $mfObj->getInvoiceURL($curlData);

    //             // Handle MyFatoorah payment response
    //             if (isset($data['Data']['InvoiceId'])) {
    //                 return response()->json([
    //                     'message' => 'Payment initiated successfully',
    //                     'invoice_id' => $data['Data']['InvoiceId'],
    //                     'payment_url' => $data['Data']['PaymentURL'], // URL for redirecting user to MyFatoorah payment page
    //                     'status' => 200
    //                 ]);
    //             } else {
    //                 return response()->json([
    //                     'status' => 500,
    //                     'message' => 'Failed to initiate payment with MyFatoorah. Please try again later.'
    //                 ]);
    //             }
    //         }
    //     } catch (Exception $e) {
    //         // Log the exception and return a generic error response
    //         Log::error('Payment initiation error: ' . $e->getMessage());

    //         return response()->json([
    //             'status' => 500,
    //             'message' => 'An error occurred while initiating payment. Please try again later.'
    //         ]);
    //     }
    // }

    // public function initiatePayment(Request $request)
    // {
    //     try {
    //         // Validate the request
    //         $validator = Validator::make($request->all(), [
    //             'patient_id' => 'required|exists:patients,id',
    //             'appointment_id' => 'required|exists:appointmentss,id',
    //             'examination_id' => 'required|exists:examination_types,id',
    //             'extra_amount' => 'nullable|numeric',
    //             'status' => 'nullable|string',
    //             'comment' => 'nullable|string',
    //             'payment_method' => 'required|string',
    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json(['status' => 400, 'message' => $validator->errors()->first()]);
    //         }

    //         // Check if the examination exists
    //         $examination = ExaminationType::findOrFail($request->examination_id);
    //         // Check if the appointment exists
    //         $appointment = Appointment::findOrFail($request->appointment_id);

    //         // Initialize MyFatoorah configuration using the doctor's ID
    //         $this->initializeConfig($appointment->doctor_id);

    //         // Check if the patient already has a reservation with the same doctor on the same day
    //         $existingReservation = Reservation::where('patient_id', $request->patient_id)
    //             ->whereHas('appointment', function ($query) use ($appointment) {
    //                 $query->where('doctor_id', $appointment->doctor_id)
    //                     ->whereDate('time_start', '=', date('Y-m-d', strtotime($appointment->time_start)));
    //             })
    //             ->where('status', '!=', 'canceled')
    //             ->first();

    //         if ($existingReservation) {
    //             return response()->json(['status' => 400, 'message' => 'You already have a reservation with this doctor on the same day.']);
    //         }

    //         // Check if the appointment is already reserved and not canceled
    //         $existingReservationForAppointment = Reservation::where('appointment_id', $request->appointment_id)
    //             ->where('status', '!=', 'canceled')
    //             ->first();

    //         if ($existingReservationForAppointment) {
    //             return response()->json(['status' => 400, 'message' => 'This appointment has already been reserved.']);
    //         }

    //         // Get the amount from the examination table
    //         $amount = $examination->amount;

    //         if ($request->payment_method === 'cash') {
    //             // Create reservation with status 'reserved' for cash payment
    //             $reservation = Reservation::create([
    //                 'patient_id' => $request->patient_id,
    //                 'appointment_id' => $request->appointment_id,
    //                 'examination_id' => $request->examination_id,
    //                 'status' => 'reserved'
    //             ]);

    //             $appointment->status = 'not-available'; // Mark the appointment as reserved
    //             $appointment->save();

    //             $payment = Payment::create([
    //                 'amount' => $amount,
    //                 'extra_amount' => $request->extra_amount,
    //                 'total' => $amount + $request->extra_amount,
    //                 'status' => 'pending',
    //                 'comment' => $request->comment,
    //                 'reservation_id' => $reservation->id,
    //                 'payment_method' => $request->payment_method, // Store the payment method
    //             ]);

    //             return response()->json([
    //                 'message' => 'Reservation and cash payment recorded successfully',
    //                 'reservation' => $reservation,
    //                 'payment' => $payment,
    //                 'status' => 200
    //             ]);
    //         } else {
    //             // Proceed with payment processing using MyFatoorah
    //             $curlData = $this->getPayLoadData($request, $examination->amount);
    //             $mfObj = new MyFatoorahPayment($this->mfConfig);

    //             try {
    //                 $data = $mfObj->getInvoiceURL($curlData, 0);
    //                 return response()->json([
    //                     'status' => 200,
    //                     'message' => 'Payment URL generated successfully',
    //                     'payment_url' => $data['invoiceURL'],
    //                 ]);
    //             } catch (Exception $e) {
    //                 Log::error('MyFatoorah API Error: ' . $e->getMessage());
    //                 return response()->json([
    //                     'status' => 500,
    //                     'message' => 'An error occurred while processing the payment. Please try again later.'
    //                 ], 500);
    //             }
    //         }
    //     } catch (\Exception $e) {
    //         Log::error('Payment Initiation Error: ' . $e->getMessage());
    //         return response()->json([
    //             'status' => 500,
    //             'message' => 'An error occurred while initiating payment. Please try again later.'
    //         ], 500);
    //     }
    // }
    // public function initiatePayment(Request $request)
    // {
    //     try {
    //         // Validate the request
    //         $validator = Validator::make($request->all(), [
    //             'patient_id' => 'required|exists:patients,id',
    //             'appointment_id' => 'required|exists:appointmentss,id',
    //             'examination_id' => 'required|exists:examination_types,id',
    //             'extra_amount' => 'nullable|numeric',
    //             'status' => 'nullable|string',
    //             'comment' => 'nullable|string',
    //             'payment_method' => 'required|string',
    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json(['status' => 400, 'message' => $validator->errors()->first()]);
    //         }

    //         // Check if the examination exists
    //         $examination = ExaminationType::findOrFail($request->examination_id);

    //         // Check if the appointment exists
    //         $appointment = Appointment::findOrFail($request->appointment_id);

    //         // Initialize MyFatoorah configuration using the doctor's ID
    //         $this->initializeConfig($appointment->doctor_id);

    //         // Check if the patient already has a reservation with the same doctor on the same day
    //         $existingReservation = Reservation::where('patient_id', $request->patient_id)
    //             ->whereHas('appointment', function ($query) use ($appointment) {
    //                 $query->where('doctor_id', $appointment->doctor_id)
    //                     ->whereDate('time_start', '=', date('Y-m-d', strtotime($appointment->time_start)));
    //             })
    //             ->where('status', '!=', 'canceled')
    //             ->first();

    //         if ($existingReservation) {
    //             return response()->json(['status' => 400, 'message' => 'You already have a reservation with this doctor on the same day.']);
    //         }

    //         // Check if the appointment is already reserved and not canceled
    //         $existingReservationForAppointment = Reservation::where('appointment_id', $request->appointment_id)
    //             ->where('status', '!=', 'canceled')
    //             ->first();

    //         if ($existingReservationForAppointment) {
    //             return response()->json(['status' => 400, 'message' => 'This appointment has already been reserved.']);
    //         }

    //         // Get the amount from the examination table
    //         $amount = $examination->amount;

    //         if ($request->payment_method === 'cash') {
    //             // Create reservation with status 'reserved' for cash payment
    //             $reservation = Reservation::create([
    //                 'patient_id' => $request->patient_id,
    //                 'appointment_id' => $request->appointment_id,
    //                 'examination_id' => $request->examination_id,
    //                 'status' => 'reserved'
    //             ]);

    //             $appointment->status = 'not-available'; // Mark the appointment as reserved
    //             $appointment->save();

    //             $payment = Payment::create([
    //                 'amount' => $amount,
    //                 'extra_amount' => $request->extra_amount,
    //                 'total' => $amount + $request->extra_amount,
    //                 'status' => 'pending',
    //                 'comment' => $request->comment,
    //                 'reservation_id' => $reservation->id,
    //                 'payment_method' => $request->payment_method, // Store the payment method
    //             ]);

    //             return response()->json([
    //                 'message' => 'Reservation and cash payment recorded successfully',
    //                 'reservation' => $reservation,
    //                 'payment' => $payment,
    //                 'status' => 200
    //             ]);
    //         } else {
    //             // Proceed with payment processing using MyFatoorah
    //             $curlData = $this->getPayLoadData($request, $examination->amount);
    //             if (!is_bool($this->mfConfig['isTest'])) {
    //                 throw new \Exception('The "isTest" key must be boolean.');
    //             }
    //             // إنشاء كائن MyFatoorahPayment بتمرير مصفوفة الإعدادات الصحيحة
    //             $mfObj = new MyFatoorahPayment([
    //                 'apiKey' => $this->mfConfig['apiKey'],
    //                 'countryCode' => $this->mfConfig['countryCode'],
    //                 'isTest' => $this->mfConfig['isTest']
    //             ]);
    //             // dd($mfObj);
    //             $data = $mfObj->getInvoiceURL($curlData, 0);
    //             dd($data);
    //             // Return the URL to the frontend
    //             return response()->json([
    //                 'status' => 200,
    //                 'message' => 'Payment URL generated successfully',
    //                 'payment_url' => $data['invoiceURL'],
    //             ]);
    //         }
    //     } catch (\Exception $e) {
    //         return response()->json(['IsSuccess' => false, 'Message' => $e->getMessage()]);
    //     }
    // }
    // public function initiatePayment(Request $request)
    // {
    //     try {
    //         // Validate the request
    //         $validator = Validator::make($request->all(), [
    //             'patient_id' => 'required|exists:patients,id',
    //             'appointment_id' => 'required|exists:appointmentss,id', // Fixed typo: 'appointmentss' to 'appointments'
    //             'examination_id' => 'required|exists:examination_types,id',
    //             'extra_amount' => 'nullable|numeric',
    //             'status' => 'nullable|string',
    //             'comment' => 'nullable|string',
    //             'payment_method' => 'required|string',
    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json(['status' => 400, 'message' => $validator->errors()->first()]);
    //         }

    //         // Check if the examination exists
    //         $examination = ExaminationType::findOrFail($request->examination_id);

    //         // Check if the appointment exists
    //         $appointment = Appointment::findOrFail($request->appointment_id);

    //         // Initialize MyFatoorah configuration using the doctor's ID
    //         $this->initializeConfig($appointment->doctor_id);

    //         // Check if the patient already has a reservation with the same doctor on the same day
    //         $existingReservation = Reservation::where('patient_id', $request->patient_id)
    //             ->whereHas('appointment', function ($query) use ($appointment) {
    //                 $query->where('doctor_id', $appointment->doctor_id)
    //                     ->whereDate('time_start', '=', date('Y-m-d', strtotime($appointment->time_start)));
    //             })
    //             ->where('status', '!=', 'canceled')
    //             ->first();

    //         if ($existingReservation) {
    //             return response()->json(['status' => 400, 'message' => 'You already have a reservation with this doctor on the same day.']);
    //         }

    //         // Check if the appointment is already reserved and not canceled
    //         $existingReservationForAppointment = Reservation::where('appointment_id', $request->appointment_id)
    //             ->where('status', '!=', 'canceled')
    //             ->first();

    //         if ($existingReservationForAppointment) {
    //             return response()->json(['status' => 400, 'message' => 'This appointment has already been reserved.']);
    //         }

    //         // Get the amount from the examination table
    //         $amount = $examination->amount;

    //         if ($request->payment_method === 'cash') {
    //             // Create reservation with status 'reserved' for cash payment
    //             $reservation = Reservation::create([
    //                 'patient_id' => $request->patient_id,
    //                 'appointment_id' => $request->appointment_id,
    //                 'examination_id' => $request->examination_id,
    //                 'status' => 'reserved'
    //             ]);

    //             $appointment->status = 'not-available'; // Mark the appointment as reserved
    //             $appointment->save();

    //             $payment = Payment::create([
    //                 'amount' => $amount,
    //                 'extra_amount' => $request->extra_amount,
    //                 'total' => $amount + $request->extra_amount,
    //                 'status' => 'pending',
    //                 'comment' => $request->comment,
    //                 'reservation_id' => $reservation->id,
    //                 'payment_method' => $request->payment_method, // Store the payment method
    //             ]);

    //             return response()->json([
    //                 'message' => 'Reservation and cash payment recorded successfully',
    //                 'reservation' => $reservation,
    //                 'payment' => $payment,
    //                 'status' => 200
    //             ]);
    //         } else {
    //             // Proceed with payment processing using MyFatoorah
    //             $curlData = $this->getPayLoadData($request, $examination->amount);
    //             //Initialize MyFatoorahPayment with the correct configuration
    //             $mfObj = new MyFatoorahPayment([
    //                 'apiKey' => $this->mfConfig['apiKey'],
    //                 'countryCode' => $this->mfConfig['countryCode'],
    //                 'isTest' => $this->mfConfig['isTest']
    //             ]);
    //             $data = $mfObj->getInvoiceURL($curlData, 0);

    //             if (empty($data['invoiceURL'])) {
    //                 throw new \Exception('Failed to generate payment URL.');
    //             }

    //             // Return the URL to the frontend
    //             return response()->json([
    //                 'status' => 200,
    //                 'message' => 'Payment URL generated successfully',
    //                 'payment_url' => $data['invoiceURL'],
    //             ]);
    //         }
    //     } catch (\Exception $e) {
    //         // Log the detailed error message
    //         Log::error('Payment initiation error: ', [
    //             'error' => $e->getMessage(),
    //             'request' => $request->all()
    //         ]);
    //         return response()->json(['status' => 500, 'message' => 'An error occurred while initiating payment. Please try again later.']);
    //     }
    // }
    public function initiatePayment(Request $request)
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'patient_id' => 'required|exists:patients,id',
                'appointment_id' => 'required|exists:appointmentss,id',
                'examination_id' => 'required|exists:examination_types,id',
                'extra_amount' => 'nullable|numeric',
                'status' => 'nullable|string',
                'comment' => 'nullable|string',
                'payment_method' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 400, 'message' => $validator->errors()->first()]);
            }
            // Check if the examination exists
            $examination = ExaminationType::find($request->examination_id);
            if (!$examination) {
                return response()->json(['status' => 400, 'message' => 'Examination type not found.']);
            }
            // Check if the appointment exists
            $appointment = Appointment::find($request->appointment_id);
            if (!$appointment) {
                return response()->json(['status' => 400, 'message' => 'Invalid appointment ID.']);
            }
            // Check if the patient already has a reservation with the same doctor on the same day
            $existingReservation = Reservation::where('patient_id', $request->patient_id)
                ->whereHas('appointment', function ($query) use ($appointment) {
                    $query->where('doctor_id', $appointment->doctor_id)
                        ->whereDate('time_start', '=', date('Y-m-d', strtotime($appointment->time_start)));
                })
                ->where('status', '!=', 'canceled')
                ->first();

            if ($existingReservation) {
                return response()->json(['status' => 400, 'message' => 'You already have a reservation with this doctor on the same day.']);
            }

            // Check if the appointment is already reserved and not canceled
            $existingReservationForAppointment = Reservation::where('appointment_id', $request->appointment_id)
                ->where('status', '!=', 'canceled')
                ->first();

            if ($existingReservationForAppointment) {
                return response()->json(['status' => 400, 'message' => 'This appointment has already been reserved.']);
            }

            // Get the amount from the examination table
            $amount = $examination->amount;

            if ($request->payment_method === 'cash') {
                // Create reservation with status 'reserved' for cash payment
                $reservation = Reservation::create([
                    'patient_id' => $request->patient_id,
                    'appointment_id' => $request->appointment_id,
                    'examination_id' => $request->examination_id,
                    'status' => 'reserved'
                ]);

                $appointment->status = 'not-available'; // Mark the appointment as reserved
                $appointment->save();

                $payment = Payment::create([
                    'amount' => $amount,
                    'extra_amount' => $request->extra_amount,
                    'total' => $amount + $request->extra_amount,
                    'status' => 'pending',
                    'comment' => $request->comment,
                    'reservation_id' => $reservation->id,
                    'payment_method' => $request->payment_method, // Store the payment method
                ]);

                return response()->json([
                    'message' => 'Reservation and cash payment recorded successfully',
                    'reservation' => $reservation,
                    'payment' => $payment,
                    'status' => 200
                ]);
            } else {
                // Fetch `api_key` for the doctor from the settings table
                $setting = Setting::where('doctor_id', $appointment->doctor_id)->first();
                if (!$setting || !$setting->api_key_myfatoorah) {
                    return response()->json(['status' => 400, 'message' => 'API key not found for the specified doctor.']);
                }
                // Set MyFatoorah configuration using the doctor's `api_key`
                $mfConfig = [
                    'apiKey'      => $setting->api_key_myfatoorah,
                    'isTest'      => true,
                    'countryCode' => config('myfatoorah.country_iso'),
                ];
                // Verify the `api_key` is set correctly
                if (empty($mfConfig['apiKey']) || !is_string($mfConfig['apiKey'])) {
                    return response()->json(['status' => 500, 'message' => 'The "apiKey" key is required and must be a string.']);
                }
                // dd($mfConfig);
                // Proceed with payment processing using MyFatoorah
                $curlData = $this->getPayLoadData($request, $examination->amount);
                $mfObj = new MyFatoorahPayment($mfConfig);

                $data = $mfObj->getInvoiceURL($curlData, 0);
                // Return the URL to the frontend
                dd($data);
                return response()->json([
                    'status' => 200,
                    'message' => 'Payment URL generated successfully',
                    'payment_url' => $data['invoiceURL'],
                ]);
            }
        } catch (\Exception $e) {
            return response()->json(['IsSuccess' => 'false', 'Message' => $e->getMessage()]);
        }
    }
    //-----------------------------------------------------------------------------------------------------------------------------------------

    private function getPayLoadData(Request $request, $amount)
    {
        $patient = Patient::find($request->patient_id);
        $appointment = Appointment::find($request->appointment_id);

        if (!$patient || !$appointment) {
            throw new \Exception('Invalid patient or appointment data');
        }

        $callbackURL = route('myfatoorah.callback');

        // Generate a unique reference ID (e.g., using the reservation ID or a UUID)
        $referenceId = uniqid('ref_'); // Or use $reservation->id if already created

        // For example, you could create a table to store this information
        Cache::put($referenceId, [
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'examination_id' => $request->examination_id,
            'extra_amount' => $request->extra_amount,
            'comment' => $request->comment
        ], 3600); // Store for 1 hour (adjust as necessary)

        return [
            'CustomerName'       => $patient->first_name . ' ' . $patient->last_name,
            'InvoiceValue'       => $amount,
            'DisplayCurrencyIso' => 'EGP',
            'CustomerEmail'      => $patient->email,
            'CallBackUrl'        => $callbackURL,
            'ErrorUrl'           => $callbackURL,
            'MobileCountryCode'  => '+20',
            'CustomerMobile'     => $patient->phone,
            'Language'           => 'en',
            'CustomerReference'  => $referenceId, // Send the short reference ID
            'InvoiceItems'       => [
                [
                    'ItemName'  => 'Examination Fee',
                    'Quantity'  => 1,
                    'UnitPrice' => $amount,
                ],
            ],
        ];
    }


    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get MyFatoorah Payment Information
     * Provide the callback method with the paymentId
     * 
     * @return Response
     */
    public function callback(Request $request)
    {
        try {
            $paymentId = $request->input('paymentId');

            $mfObj = new MyFatoorahPaymentStatus($this->mfConfig);

            $data = $mfObj->getPaymentStatus($paymentId, 'PaymentId');

            // Fetch the customer reference from the payment data
            $customerReference = Cache::get($data->CustomerReference);
            // Get the examination record using the examination_id from the customer reference
            $examination = ExaminationType::find($customerReference['examination_id']);
            if (!$examination) {
                return response()->json(['status' => 400, 'message' => 'Examination type not found.']);
            }
            // Get the API key for MyFatoorah using doctor_id
            $setting = Setting::where('doctor_id', $examination->doctor_id)->first();
            if (!$setting || !$setting->api_key_myfatoorah) {
                return response()->json(['status' => 400, 'message' => 'API key not found for the specified doctor.']);
            }
            // Configure MyFatoorah settings with the doctor's API key
            // $this->mfConfig = [
            //     'apiKey'      => [$setting->api_key_myfatoorah], // Ensure this is a string
            //     'isTest'      => config('myfatoorah.test_mode'),
            //     'countryCode' => config('myfatoorah.country_iso'),
            // ];

            // // Ensure the API key is correctly set
            // if (empty($this->mfConfig['apiKey']) || !is_string($this->mfConfig['apiKey'])) {
            //     return response()->json(['status' => 500, 'message' => 'The "apiKey" key is required and must be a string.']);
            // }
            // Reinitialize the MyFatoorah object with the updated config
            $mfObj = new MyFatoorahPaymentStatus($this->mfConfig);
            // Fetch the payment status again with the correct config
            $data = $mfObj->getPaymentStatus($paymentId, 'PaymentId');
            // Handle the payment status
            switch ($data->InvoiceStatus) {
                case 'Paid':
                    // Handle successful payment
                    $customerReference = Cache::get($data->CustomerReference);
                    if (!$customerReference) {
                        return response()->json(['status' => 400, 'message' => 'Invalid customer reference data.']);
                    }
                    // Create the reservation and update the appointment status
                    $reservation = Reservation::create([
                        'patient_id' => $customerReference['patient_id'],
                        'appointment_id' => $customerReference['appointment_id'],
                        'examination_id' => $customerReference['examination_id'],
                        'status' => 'confirmed',
                    ]);

                    $appointment = Appointment::find($customerReference['appointment_id']);
                    if ($appointment) {
                        $appointment->status = 'not-available';
                        $appointment->save();
                    }

                    // Create the payment record
                    $payment = Payment::create([
                        'amount' => $data->InvoiceItems[0]->UnitPrice,
                        'extra_amount' => $customerReference['extra_amount'] ?? 0,
                        'total' => $data->InvoiceItems[0]->UnitPrice + ($customerReference['extra_amount'] ?? 0),
                        'status' => 'confirmed',
                        'comment' => $customerReference['comment'] ?? '',
                        'reservation_id' => $reservation->id,
                        'payment_method' => 'visa',
                    ]);

                    // Save transaction details in the database
                    Transaction::create([
                        'invoice_id' => $data->InvoiceId,
                        'invoice_status' => $data->InvoiceStatus,
                        'invoice_reference' => $data->InvoiceReference,
                        'invoice_value' => $data->InvoiceValue,
                        'due_deposit' => $data->DueDeposit,
                        'deposit_status' => $data->DepositStatus,
                        'invoice_display_value' => $data->InvoiceDisplayValue,
                        'customer_name' => $data->CustomerName,
                        'customer_mobile' => $data->CustomerMobile,
                        'customer_email' => $data->CustomerEmail,
                        'customer_reference' => $data->CustomerReference,
                        'transaction_id' => $data->InvoiceTransactions[0]->TransactionId,
                        'payment_gateway' => $data->InvoiceTransactions[0]->PaymentGateway,
                        'transaction_status' => $data->InvoiceTransactions[0]->TransactionStatus,
                        'transaction_date' => $data->InvoiceTransactions[0]->TransactionDate,
                        'reference_id' => $data->InvoiceTransactions[0]->ReferenceId,
                        'track_id' => $data->InvoiceTransactions[0]->TrackId,
                        'authorization_id' => $data->InvoiceTransactions[0]->AuthorizationId,
                        'transaction_value' => $data->InvoiceTransactions[0]->TransationValue,
                        'paid_currency' => $data->InvoiceTransactions[0]->PaidCurrency,
                        'paid_currency_value' => $data->InvoiceTransactions[0]->PaidCurrencyValue,
                        'total_service_charge' => $data->InvoiceTransactions[0]->TotalServiceCharge,
                        'vat_amount' => $data->InvoiceTransactions[0]->VatAmount,
                        'ip_address' => $data->InvoiceTransactions[0]->IpAddress,
                        'country' => $data->InvoiceTransactions[0]->Country,
                        'invoice_error' => $data->InvoiceError,
                        'error_code' => $data->InvoiceTransactions[0]->ErrorCode ?? '',
                    ]);

                    // Redirect to the "Thank You" page
                    $redirectUrl = 'http://localhost:3000/thank-you?' . http_build_query([
                        'amount' => $payment->amount,
                        'extra_amount' => $payment->extra_amount,
                        'total' => $payment->total,
                        'status' => $payment->status,
                        "invoice_id" => $data->InvoiceId,
                        'reservation_id' => $payment->reservation_id,
                        'payment_method' => $data->InvoiceTransactions[0]->PaymentGateway,
                        'invoice_reference' => $data->InvoiceReference,
                    ]);

                    return redirect()->away($redirectUrl);

                case 'Failed':
                    $errorMessage = 'Payment failed';
                    if (!empty($data->InvoiceError)) {
                        $errorMessage .= ': ' . $data->InvoiceError;
                    }
                    if (strpos($data->InvoiceError, 'Do not honour') !== false) {
                        $errorMessage .= '. Please contact your bank for more details or try a different payment method.';
                    }
                    return response()->json(['status' => 400, 'message' => $errorMessage]);

                case 'Expired':
                    return response()->json(['status' => 400, 'message' => 'Payment expired.']);

                case 'Pending':
                    return response()->json(['status' => 400, 'message' => 'Payment is still pending.']);

                default:
                    return response()->json(['status' => 400, 'message' => 'Unknown payment status.']);
            }
        } catch (\Exception $e) {
            Log::error('Callback processing error:', ['message' => $e->getMessage()]);
            return response()->json(['IsSuccess' => 'false', 'Message' => $e->getMessage()]);
        }
    }





    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Example on how to Display the enabled gateways at your MyFatoorah account to be displayed on the checkout page
     * Provide the checkout method with the order id to display its total amount and currency
     * 
     * @return View
     */
    public function checkout()
    {
        try {
            //You can get the data using the order object in your system
            $orderId = request('oid') ?: 147;
            $order   = $this->getTestOrderData($orderId);

            //You can replace this variable with customer Id in your system
            $customerId = request('customerId');

            //You can use the user defined field if you want to save card
            $userDefinedField = config('myfatoorah.save_card') && $customerId ? "CK-$customerId" : '';

            //Get the enabled gateways at your MyFatoorah acount to be displayed on checkout page
            $mfObj          = new MyFatoorahPaymentEmbedded($this->mfConfig);
            $paymentMethods = $mfObj->getCheckoutGateways($order['total'], $order['currency'], config('myfatoorah.register_apple_pay'));

            if (empty($paymentMethods['all'])) {
                throw new Exception('noPaymentGateways');
            }

            //Generate MyFatoorah session for embedded payment
            $mfSession = $mfObj->getEmbeddedSession($userDefinedField);

            //Get Environment url
            // $isTest = $this->mfConfig['isTest'];
            // $vcCode = $this->mfConfig['countryCode'];

            $countries = MyFatoorah::getMFCountries();
            // $jsDomain  = ($isTest) ? $countries[$vcCode]['testPortal'] : $countries[$vcCode]['portal'];

            return view('myfatoorah.checkout', compact('mfSession', 'paymentMethods', 'jsDomain', 'userDefinedField'));
        } catch (Exception $ex) {
            $exMessage = __('myfatoorah.' . $ex->getMessage());
            return view('myfatoorah.error', compact('exMessage'));
        }
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Example on how the webhook is working when MyFatoorah try to notify your system about any transaction status update
     */
    public function webhook(Request $request)
    {
        try {
            //Validate webhook_secret_key
            $secretKey = config('myfatoorah.webhook_secret_key');
            if (empty($secretKey)) {
                return response(null, 404);
            }

            //Validate MyFatoorah-Signature
            $mfSignature = $request->header('MyFatoorah-Signature');
            if (empty($mfSignature)) {
                return response(null, 404);
            }

            //Validate input
            $body  = $request->getContent();
            $input = json_decode($body, true);
            if (empty($input['Data']) || empty($input['EventType']) || $input['EventType'] != 1) {
                return response(null, 404);
            }

            //Validate Signature
            if (!MyFatoorah::isSignatureValid($input['Data'], $secretKey, $mfSignature, $input['EventType'])) {
                return response(null, 404);
            }

            //Update Transaction status on your system
            $result = $this->changeTransactionStatus($input['Data']);

            return response()->json($result);
        } catch (Exception $ex) {
            $exMessage = __('myfatoorah.' . $ex->getMessage());
            return response()->json(['IsSuccess' => false, 'Message' => $exMessage]);
        }
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------
    private function changeTransactionStatus($inputData)
    {
        //1. Check if orderId is valid on your system.
        $orderId = $inputData['CustomerReference'];

        //2. Get MyFatoorah invoice id
        $invoiceId = $inputData['InvoiceId'];

        //3. Check order status at MyFatoorah side
        if ($inputData['TransactionStatus'] == 'SUCCESS') {
            $status = 'Paid';
            $error  = '';
        } else {
            $mfObj = new MyFatoorahPaymentStatus($this->mfConfig);
            $data  = $mfObj->getPaymentStatus($invoiceId, 'InvoiceId');

            $status = $data->InvoiceStatus;
            $error  = $data->InvoiceError;
        }

        $message = $this->getTestMessage($status, $error);

        //4. Update order transaction status on your system
        return ['IsSuccess' => true, 'Message' => $message, 'Data' => $inputData];
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------
    private function getTestOrderData($orderId)
    {
        return [
            'total'    => 15,
            'currency' => 'EGP'
        ];
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------
    private function getTestMessage($status, $error)
    {
        if ($status == 'Paid') {
            return 'Invoice is paid.';
        } else if ($status == 'Failed') {
            return 'Invoice is not paid due to ' . $error;
        } else if ($status == 'Expired') {
            return $error;
        }
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------
}