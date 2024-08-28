<?php

namespace App\Http\Controllers\Api;

use DateTime;
use DateInterval;
use App\Models\ClinicInfo;
use App\Models\Appointment;
use App\Models\Reservation;
use Illuminate\Http\Request;
use App\Http\Traits\GeneralTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class AppointmentController extends Controller
{
    use GeneralTrait;

    private  $rules = [
        'time_start'  => 'nullable|date|after_or_equal:now',
        'time_end'    => 'nullable|date|after:time_start',
        'doctor_id'   => 'required|exists:users,id',
        'status' => 'required|string|in:available,not-available',
        'duration'    => 'nullable|numeric|min:1'
    ];

    public function save(Request $request)
    {
        $validator = $this->validateRequest($request);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Error.',
                'Error' => $validator,
                'status' => 400
            ]);
        }

        if (!$request->time_start || !$request->time_end) {
            return response()->json([
                'message' => 'Invalid time values.',
                'status' => 400
            ]);
        }

        $time_start = \Carbon\Carbon::parse($request->time_start);
        $time_end = \Carbon\Carbon::parse($request->time_end);

        $now = \Carbon\Carbon::now();
        if ($time_start->lt($now)) {

            return response()->json([
                'message' => 'You cannot book an appointment in the past.',
                'status' => 400
            ]);
        }

        $time_start = $time_start->format('Y-m-d H:i:s');
        $time_end = $time_end->format('Y-m-d H:i:s');

        $conflict = $this->hasTimeConflict($time_start, $time_end, $request->doctor_id);
        if ($conflict !== false) {
            return response()->json([
                'message' => 'The appointment time conflicts with an existing appointment',
                'status' => 400
            ]);
            // return $this->returnErrorResponse(__('The appointment time conflicts with an existing appointment'), null, $conflict);
        }

        $appointment = $request->duration
            ? $this->createDurationAppointments($request, $time_start, $time_end)
            : $this->createSingleAppointment($request, $time_start, $time_end);

        // Check if the appointment was created
        if ($appointment) {
            return response()->json([
                'message' => 'Appointment added successfully.',
                'data' => $appointment,
                'status' => 200
            ]);
        } else {
            return response()->json([
                'message' => 'Failed to add appointment.',
                'status' => 400
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        $rules = [
            'time_start'  => 'nullable|date',
            'time_end'    => 'nullable|date|after:time_start',
            'doctor_id'   => 'nullable|exists:users,id',
            'status' => 'required|string|in:available,not-available',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'message' => 'Validation Error', 'errors' => $validator->errors()]);
        }

        $appointment = Appointment::find($id);
        if (!$appointment) {
            return response()->json(['status' => 400, 'message' => 'Appointment not found']);
        }

        // Check if the appointment has a confirmed reservation
        $confirmedReservation = $appointment->reservations()->where('status', 'confirmed')->exists();
        if ($confirmedReservation) {
            return response()->json(['status' => 400, 'message' => 'You cannot edit an appointment with a confirmed reservation']);
        }

        $data = [];

        if ($request->has('time_start')) {
            $time_start = \Carbon\Carbon::parse($request->time_start);
            $now = \Carbon\Carbon::now();
            if ($time_start->lt($now)) {
                return response()->json(['status' => 400, 'message' => 'You cannot book an appointment in the past']);
            }
            $data['time_start'] = $time_start->format('Y-m-d H:i:s');
        } else {
            $data['time_start'] = $appointment->time_start;
        }

        if ($request->has('time_end')) {
            $time_end = \Carbon\Carbon::parse($request->time_end);
            $data['time_end'] = $time_end->format('Y-m-d H:i:s');
        } else {
            $data['time_end'] = $appointment->time_end;
        }

        if ($request->has('doctor_id')) {
            $data['doctor_id'] = $request->doctor_id;
        } else {
            $data['doctor_id'] = $appointment->doctor_id;
        }

        if ($request->has('status')) {
            $data['status'] = $request->status;
        } else {
            $data['status'] = $appointment->status;
        }

        // Update the appointment
        $appointment->update($data);

        // If the status is 'available', update all related reservations to 'canceled'
        if ($data['status'] === 'available') {
            $appointment->reservations()->update([
                'status' => 'canceled',
            ]);
        }

        return response()->json(['status' => 200, 'message' => 'Appointment updated successfully', 'data' => $appointment]);
    }


    public function delete($id)
    {
        $appointment = Appointment::find($id);

        if (!$appointment) {
            return $this->returnErrorResponse(__('general.found_error'));
        }
        // تحقق مما إذا كان هناك حجز مرتبط بهذا الموعد وأن حالته مؤكدة
        $reservation = Reservation::where('appointment_id', $id)->where('status', 'confirmed')->first();

        if ($reservation) {
            return response()->json([
                'status'  => 400,
                'message' => 'can not deleted confirmed reservation',
            ]);
        }

        $appointment->delete();
        return $this->returnSuccessResponse(__('general.delete_success'), $appointment);
    }

    public function deleteAll()
    {
        $undeletableAppointments = Reservation::where('status', 'confirmed')
            ->pluck('appointment_id')
            ->toArray();

        $deletedCount = Appointment::whereNotIn('id', $undeletableAppointments)->delete();

        if ($deletedCount > 0) {
            return response()->json([
                'status' => 200,
                'message' => 'All appointments deleted successfully.',
                'deleted_count' => $deletedCount,
            ]);
        }

        return response()->json([
            'status' => 400,
            'message' => 'No appointments were deleted, or all appointments have confirmed reservations.',
        ]);
    }


    public function show($id)
    {
        $appointment = Appointment::find($id);
        if (!$appointment) {
            return $this->returnErrorResponse(__('general.found_error'));
        }

        return $this->returnSuccessResponse(__('general.found_success'), $appointment);
    }

    public function getAppointments(Request $request, $doctor_id = null)
    {
        $perPage = $request->input('per_page', 10);

        if ($doctor_id) {
            return $this->getAppointmentsByDoctor($doctor_id);
        }

        $appointments = Appointment::with('doctor:id,first_name,last_name')->paginate($perPage);

        if ($appointments->isEmpty()) {
            return response()->json(['status' => 404, 'message' => 'No appointments found.']);
        }

        // Transform the appointments collection
        $appointments->getCollection()->transform(function ($appointment) {
            return [
                'id' => $appointment->id,
                'time_start' => $appointment->time_start,
                'time_end' => $appointment->time_end,
                'doctor' => $appointment->doctor ? [
                    'id' => $appointment->doctor->id,
                    'first_name' => $appointment->doctor->first_name,
                    'last_name' => $appointment->doctor->last_name,
                ] : null,
                'status' => $appointment->status,
            ];
        });

        return response()->json([
            'status' => 200,
            'data' => $appointments->items(),
            'current_page' => $appointments->currentPage(),
            'last_page' => $appointments->lastPage(),
            'per_page' => $appointments->perPage(),
            'total' => $appointments->total(),
        ]);
    }

    // public function countAppointmentsByMonth(Request $request)
    // {
    //     $startDate = $request->input('time_start', now()->startOfYear());
    //     $endDate = $request->input('time_end', now()->endOfYear());

    //     $appointmentsCount = Appointment::select(
    //         DB::raw('YEAR(created_at) as year'),
    //         DB::raw('MONTH(created_at) as month'),
    //         DB::raw('COUNT(*) as count')
    //     )
    //         ->whereBetween('created_at', [$startDate, $endDate])
    //         ->groupBy(DB::raw('YEAR(created_at), MONTH(created_at)'))
    //         ->orderBy('year')
    //         ->orderBy('month')
    //         ->get();

    //     return response()->json([
    //         'data' => $appointmentsCount,
    //         'status' => 200
    //     ]);
    // }
    public function getAppointmentsByDoctor($doctor_id)
    {
        $appointments = Appointment::where('doctor_id', $doctor_id)->with('doctor:id,first_name,last_name')->get();

        if ($appointments->isEmpty()) {
            return response()->json(['status' => 404, 'message' => 'No appointments found for the specified doctor.']);
        }

        $appointments = $appointments->map(function ($appointment) {
            return [
                'id' => $appointment->id,
                'time_start' => $appointment->time_start,
                'time_end' => $appointment->time_end,
                'doctor' => $appointment->doctor ? [
                    'id' => $appointment->doctor->id,
                    'first_name' => $appointment->doctor->first_name,
                    'last_name' => $appointment->doctor->last_name,
                ] : null,
                'status' => $appointment->status,
            ];
        });

        return response()->json(['status' => 200, 'data' => $appointments], 200);
    }

    private function validateRequest($request)
    {
        return Validator::make($request->all(), $this->rules);
    }

    private function hasTimeConflict($time_start, $time_end, $doctorId, $appointmentId = null)
    {
        $conflictingAppointments = Appointment::where('doctor_id', $doctorId)
            ->where(function ($query) use ($time_start, $time_end, $appointmentId) {
                $query->whereBetween('time_start', [$time_start, $time_end])
                    ->orWhereBetween('time_end', [$time_start, $time_end])
                    ->orWhere(function ($query) use ($time_start, $time_end) {
                        $query->where('time_start', '<=', $time_start)
                            ->where('time_end', '>=', $time_end);
                    });
                if ($appointmentId) {
                    $query->where('id', '<>', $appointmentId);
                }
            })->get();

        if ($conflictingAppointments->isNotEmpty()) {
            return $conflictingAppointments;
        }

        return false;
    }

    private function createSingleAppointment($request, $time_start, $time_end)
    {
        return Appointment::create([
            'time_start'  => $time_start,
            'time_end'    => $time_end,
            'doctor_id'   => $request->doctor_id,
            // 'patient_id'  => $request->patient_id,
            'status'      => $request->status, // or any default status
        ]);
    }

    private function createDurationAppointments($request, $time_start, $time_end)
    {
        $duration = $request->duration;
        $begin = new DateTime($time_start);
        $end = new DateTime($time_end);
        $data = [];

        // Create repeated appointments
        while ($begin <= $end) {
            $start = $begin->format('Y-m-d H:i:s');
            $begin->add(new DateInterval('PT' . $duration . 'M'));
            if ($begin > $end) break;

            $end_time = $begin->format('Y-m-d H:i:s');
            $data[] = [
                'time_start'  => $start,
                'time_end'    => $end_time,
                'doctor_id'   => $request->doctor_id,
                // 'patient_id'  => $request->patient_id,
                'status'      => $request->status, // or any default status
            ];
        }
        Appointment::insert($data);
        return $data;
    }

    private function returnErrorResponse($message, $validator = null, $conflict = null)
    {
        $response = ['status' => 400, 'message' => $message];
        if ($validator) {
            $response['errors'] = $validator->errors();
        }
        if ($conflict) {
            $response['conflicts'] = $conflict;
        }
        return response()->json($response, 400);
    }

    private function returnSuccessResponse($message, $data)
    {
        return response()->json(['status' => 200, 'message' => $message, 'data' => $data], 200);
    }


    // private $rules =  [
    //     'from'          => 'required',
    //     'to'            => 'required',
    //     'duration'      => 'numeric',
    //     'doctor_id'     => 'required',
    // ];
    // public function list(Request $request)
    // {
    //     //return response()->json(date('Y-m-d H:i:s'));
    //     $data = Appointment::with('visit', 'visit.patient')->where('doctor_id', $request->doctor_id)->where('from', '>=', date('Y-m-d H:i:s'))->orderBy('from', 'asc')->get();
    //     //return response()->json($data);

    //     $clinic_info = ClinicInfo::first();
    //     foreach($data as $d)
    //     {
    //         if($d->visit)
    //         {
    //             if($d->visit->type == 'diagnosis')
    //             {
    //                 $d->visit->amount = $clinic_info->amount;
    //             }else{

    //                 $d->visit->amount = $clinic_info->consultation_amount;
    //             }
    //         }
    //     }
    //     return $this->returnData(__('general.found_success'), 'data', $data);
    // }

    // public function save(Request $request)
    // {
    //     //return date("h:i:sa");

    //     $validator = $this->validateRequest($request, $this->rules);
    //     if($validator->fails())
    //         return $this->returnErrorResponse(__('general.validate_error'), $validator);


    //     if($request->to <= $request->from)
    //         return $this->returnErrorResponse(__('general.errorAppointment'));




    //     $data = $this->checkFromToAppointment($request->from, $request->to,null, $request->doctor_id);
    //    // return response()->json($data);
    //     if($data && count($data))
    //         return $this->returnErrorResponse(__('general.errorAppointment'));



    //     if($request->duration){
    //             $duration = $request->duration;

    //             $begin = new DateTime( $request->from);
    //             $end   = new DateTime( $request->to);

    //             $dataRes = $this->checkFromToAppointment($begin, $end,null ,$request->doctor_id);
    //             //return response()->json($dataRes);
    //             if($dataRes && count($dataRes))
    //                 return $this->returnErrorResponse(__('general.errorAppointment'));

    //             $dataRes = NULL;

    //             //get Dates
    //             $start_date = date('Y-m-d', strtotime($request->from));
    //             $end_date = date('Y-m-d', strtotime($request->to));

    //             //get times
    //             $start_time =   date_format($begin, 'H:i:s');
    //             $end_time   =   date_format($end, 'H:i:s');

    //             //this snippets of code to create same appointments in different days.
    //             if($start_date <= $end_date)
    //             {
    //                 $data = [];
    //                 while($start_date <= $end_date){
    //                     $begin_repeated = new DateTime($start_date.' ' . $start_time);
    //                     $end_repeated   = new DateTime($start_date. ' ' . $end_time);

    //                     $temp_result = $this->addAppointmentDurationHelper($begin_repeated, $end_repeated, $duration, $request->doctor_id);
    //                     $data = array_merge($data, $temp_result);
    //                     $start_date = date('Y-m-d', strtotime($start_date . ' +1 day'));
    //                 }
    //             }

    //             //store the list
    //             $this->addAppointmentsHelper($data);
    //     }else{
    //         //single appointment
    //         $from = date('Y-m-d H:i:s',strtotime($request->from));
    //         $to = date('Y-m-d H:i:s',strtotime($request->to));
    //         $data = $this->addAppointmentHelper($from, $to, $request->doctor_id);
    //     }

    //       //  Appointment::insert($data);
    //    return $this->returnSuccessResponse(__('general.add_success'), $data);
    // }

    // //prepare the arry to be inserted in database (use duration).
    // private function addAppointmentDurationHelper($begin, $end, $duration, $doctor_id)
    // {
    //     $data = [];
    //     for($i = $begin; $i <= $end; ){

    //         $from = $i->format('Y-m-d H:i:s');

    //         $i->add(new DateInterval('PT' . $duration . 'M'));  //increase the duration

    //         if($i > $end)
    //             break;

    //         $to = $i->format('Y-m-d H:i:s');

    //         $data [] = [
    //                     'from'  => $from,
    //                     'to'  => $to,
    //                     'status'  => 0,
    //                     'doctor_id'  => $doctor_id,
    //                 ];
    //     }
    //     return $data;

    // }

    // //Method to check the appointment to avoit appointments conflict.
    // private function checkFromToAppointment($from, $to, $id=null, $doctor_id=null)
    // {

    //         //check to avoid conflict
    //         $data = \App\Models\Appointment::where(function($query)use ($from, $to,$id,$doctor_id){
    //             $query->where('from', '<=', $from)->Where('to', '>', $from);
    //             if($doctor_id !== null)
    //                 $query->where('doctor_id', $doctor_id);

    //             if($id !== null) //except the updated one.
    //                 $query->where('id', '<>', $id);
    //         })->orWhere(function($query)use($from, $to, $id,$doctor_id){
    //             $query->where('from', '<', $to)->Where('to', '>', $to);
    //             if($doctor_id !== null)
    //                 $query->where('doctor_id', $doctor_id);

    //             if($id !== null)
    //                 $query->where('id', '<>', $id);

    //         })->orWhere(function($query)use($from, $to, $id,$doctor_id){
    //         $query->where('from', '>', $from)
    //             ->Where('from', '<', $to)
    //             ->where('to', '>', $from)
    //             ->Where('to', '<', $to);

    //             if($doctor_id !== null)
    //                 $query->where('doctor_id', $doctor_id);

    //             if($id !== null)
    //                 $query->where('id', '<>', $id);
    //         })->get();


    //     return $data;
    // }

    // //Save array of appointments.
    // private function addAppointmentsHelper($data)
    // {
    //     $data = Appointment::insert($data);
    //     return $data;
    // }


    // //Save sigle appointment
    // private function addAppointmentHelper($from, $to, $doctor_id)
    // {
    //     $data = Appointment::create([
    //         'from'  => $from,
    //         'to'  => $to,
    //         'status'  => 0,
    //         'doctor_id'  => $doctor_id,
    //     ]);
    //     return $data;
    // }
    // public function update($subdomain, $id, Request $request)
    // {
    //     $validator = $this->validateRequest($request, $this->rules);
    //     if($validator->fails())
    //         return $this->returnErrorResponse(__('general.validate_error'), $validator);

    //     if($request->to <= $request->from)
    //         return $this->returnErrorResponse(__('general.errorAppointment'));



    //     $data = $this->checkFromToAppointment($request->from, $request->to, $id, $request->doctor_id);

    //     if($data && count($data))
    //         return $this->returnErrorResponse(__('general.errorAppointment'));

    //     $data = Appointment::find($id);
    //     if(!$data)
    //         return $this->returnErrorResponse(__('general.found_error'));


    //     $data->from = $request->from;
    //     $data->to = $request->to;
    //     $data->save();

    //     return $this->returnSuccessResponse(__('general.edit_success'), $data);
    // }

    // public function delete($subdomain,$id)
    // {
    //     $data = Appointment::find($id);
    //     if(!$data)
    //         return $this->returnErrorResponse(__('general.found_error'));

    //     $data->delete();

    //     return $this->returnSuccessResponse(__('general.delete_success'), $data);
    // }

    // public function filterAppointments(Request $request)
    // {

    //     if($request->filter != ''){
    //         $strings = $request->filter;
    //         $arr = explode(',', $strings);    
    //         $data = Appointment::with('visit', 'visit.patient')->where('doctor_id', $request->doctor_id)->whereIn('status', $arr)->where('from', '>=', date('Y-m-d H:i:s'))->get();
    //     }
    //     else{
    //         $data = Appointment::with('visit', 'visit.patient')->where('doctor_id', $request->doctor_id)->where('from', '>=', date('Y-m-d H:i:s'))->get();
    //     }

    //     $clinic_info = ClinicInfo::first();
    //     foreach($data as $d)
    //     {
    //         if($d->visit)
    //         {
    //             if($d->visit->type == 'diagnosis')
    //             {
    //                 $d->visit->amount = $clinic_info->amount;
    //             }else{

    //                 $d->visit->amount = $clinic_info->consultation_amount;
    //             }
    //         }
    //     }

    //     return $this->returnData(__('general.found_success'),'data', $data);
    // }
}
