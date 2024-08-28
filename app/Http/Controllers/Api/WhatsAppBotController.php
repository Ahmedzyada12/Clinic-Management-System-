<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Visit;
use App\Models\Appointment;
use App\Models\WhatsappBotMessage;
use App\Http\Traits\GeneralTrait;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use App\Models\ClinicInfo;

class WhatsAppBotController extends Controller
{
    use GeneralTrait;
    public function whatsappWebhook($subdomain, Request $request)
    {
        $message = '';
        $number = '';
        $user_name = 'new User';
        $header_params = [];
        $body_params = [];
        $body_params_obj = new \stdClass();
        if ($request->entry && count($request->entry) > 0) {
            $first_entry = $request->entry;
            $first_entry = $first_entry[0];
            if ($first_entry['changes'] && count($first_entry['changes'])) {
                $first_change = $first_entry['changes'];
                $first_change = $first_change[0];



                if ($first_change['value']) {
                    $value = $first_change['value'];
                    if ($value['messages'] && count($value['messages']) > 0) {
                        $first_message = $value['messages'];
                        $first_message = $first_message[0];


                        if ($first_message['type'] == 'text' && $first_message['text']) {
                            $first_message = $first_message['text'];
                            $message = $first_message['body'];
                        } elseif ($first_message['type'] == 'button' && isset($first_message['button']) && isset($first_message['button']['text'])) {
                            $message = $first_message['button']['text'];
                        }
                    }


                    if ($value['contacts'] && count($value['contacts']) > 0) {
                        $contact = $value['contacts'];
                        $contact = $contact[0];

                        //get the number
                        if ($contact['wa_id']) {
                            $number = $contact['wa_id'];
                        }

                        if ($contact['profile']) {
                            $name_object = $contact['profile'];
                            if (isset($name_object) && isset($name_object['name']) && $name_object['name'] != "") {
                                $user_name = $name_object['name'];
                            }
                        }
                    }
                }
            }
        }



        // $message = '123';
        // $number = '201110652177';


        // string to lower case support arabic and english.
        $message = mb_strtolower($message, 'UTF-8');

        if ($message && $number) {

            //remove the country code from number
            $num = ltrim($number, '2');


            $patient = User::where('role', 2)->where('phone', $num)->first();



            //check the previous operation if it's book
            // book for the patient


            $last_message = WhatsappBotMessage::where('number', $number)->latest()->first();
            if ($last_message && $last_message->operation == WhatsappBotMessage::$BOOK_OPERATION) {
                $extra_date = json_decode($last_message->extra_date);
                if ($extra_date == null) {
                    $extra_date = new \stdClass();
                }


                if (!isset($extra_date->doctor_name) && !isset($extra_date->patient_number)) {

                    $doctor = User::where('role', 0)->where('first_name', 'LIKE', '%' . $message . '%')->orWhere('last_name', 'LIKE', '%' . $message . '%')->get()->first();

                    //if the user write a wrong doctor name.
                    if (!$doctor) {
                        $note = env('whatsapp_template_language') == 'en' ? 'Enter a correct doctor name' : 'أدخل أسم الدكتور بشكل صحيح';

                        array_push($body_params, $this->makeObjectHelper($note));
                        return $this->sendMsg($number, 'note', env('whatsapp_template_language'), $body_params);
                    }


                    $extra_date->doctor_name    = $message;
                    $extra_date->doctor_id      = $doctor->id;
                    $last_message->extra_date   = json_encode($extra_date);
                    $last_message->save();

                    return $this->sendMsg($number, 'get_number', env('whatsapp_template_language'));
                } elseif (isset($extra_date->doctor_name) && !isset($extra_date->patient_number) && !isset($extra_date->canceled)) {
                    $extra_date->patient_number = $message;
                    $last_message->extra_date = json_encode($extra_date);
                    $last_message->save();


                    $patient = $this->checkPatientWithNumber($extra_date->patient_number);
                    if (!$patient) {
                        $extra_date->canceled = 1;
                        $last_message->extra_date = json_encode($extra_date);
                        $last_message->save();

                        array_push($body_params, $this->makeObjectHelper($user_name));
                        return $this->sendMsg($number, 'no_patient', env('whatsapp_template_language'), $body_params);
                    }

                    $appointment = Appointment::where('doctor_id', $extra_date->doctor_id)->where('status', 0)->where('from', '>=', date('Y-m-d H:i:s'))->orderBy('from', 'asc')->get()->first();
                    if (!$appointment) {
                        $extra_date->canceled = 1;
                        $last_message->extra_date = json_encode($extra_date);
                        $last_message->save();

                        return $this->sendMsg($number, 'no_appointments', env('whatsapp_template_language'));
                    }


                    $only_one_appointment_chk = $this->checkOnlyOneAppointment($patient, $appointment);

                    if ($only_one_appointment_chk) {

                        $extra_date->canceled = 1;
                        $last_message->extra_date = json_encode($extra_date);
                        $last_message->save();


                        return $this->sendMsg($number, 'only_one_book', env('whatsapp_template_language'));
                    }

                    if (env('whatsapp_template_language') == 'ar') {
                        $msg = 'ادخل نوع الزيارة كشف - استشارة';
                    } else {
                        $msg = 'enter the type of visit diagnosis - follow up';
                    }

                    array_push($body_params, $this->makeObjectHelper($msg));
                    return $this->sendMsg($number, 'note', env('whatsapp_template_language'), $body_params);
                } elseif (isset($extra_date->doctor_name) && isset($extra_date->patient_number) && !isset($extra_date->type) && !isset($extra_date->canceled)) {

                    if (!in_array($message, ['diagnosis', 'follow up', 'استشارة', 'كشف'])) {
                        if (env('whatsapp_template_language') == 'ar') {
                            $msg = 'يرجي ادخال النوع الزيارة بشكل صحيح كشف - استشارة';
                        } else {
                            $msg = 'enter the type of visit correctly diagnosis - follow up';
                        }

                        array_push($body_params, $this->makeObjectHelper($msg));
                        return $this->sendMsg($number, 'note', env('whatsapp_template_language'), $body_params);
                    }
                    if ($message == 'كشف' || $message == 'diagnosis') {
                        $type = 'diagnosis';
                    } else {
                        $type = 'follow';
                    }
                    $extra_date->type = $type;
                    $last_message->extra_date = json_encode($extra_date);
                    $last_message->save();

                    $patient = $this->checkPatientWithNumber($extra_date->patient_number);

                    $appointment = Appointment::where('doctor_id', $extra_date->doctor_id)->where('status', 0)->where('from', '>=', date('Y-m-d H:i:s'))->orderBy('from', 'asc')->get()->first();
                    if (!$appointment) {

                        return $this->sendMsg($number, 'no_appointments', env('whatsapp_template_language'));
                    }

                    $visit = Visit::create([
                        'user_id'           => $patient->id,
                        'appointment_id'   => $appointment->id,
                        'type'   => $extra_date->type,
                    ]);

                    $appointment->status = 1;
                    $appointment->save();
                    
                    
                    
                    //return $this->sendInfoMsg($visit, $patient);
                    
                    //send inquiry messages
                        $patient_count = Appointment::where('doctor_id', $extra_date->doctor_id)->where('from', 'like', '%' . date('Y-m-d', strtotime($visit->appointment->from)) . '%')->where('from', '<', $visit->appointment->from)->count();

                $patient_count += 1;
                
                $current_number = Appointment::where('doctor_id', $extra_date->doctor_id)->whereHas('visit', function($q){
                        $q->where('status', 1);
                    })->where('from', 'LIKE', '%' . date('Y-m-d') . '%')->count();
                    
                  $current_number += 1;
        
                //send the information via whatsapp
                array_push($body_params, $this->makeObjectHelper($patient->first_name . ' ' . $patient->last_name));
        
                if (env('whatsapp_template_language') == 'ar')
                $appointment = date('h:i A', strtotime($visit->appointment->from)) . '\nالتاريخ ' . date('d-m-Y ', strtotime($visit->appointment->from )) . ' \nالدور الحالي ' . $current_number; 
                else
                $appointment = date('h:i A', strtotime($visit->appointment->from)) . '\nDate ' . date('d-m-Y ', strtotime($visit->appointment->from)) . '\nCurrent: ' . $current_number;
                array_push($body_params, $this->makeObjectHelper($appointment));
        
                array_push($body_params, $this->makeObjectHelper($patient_count));
        
                return $this->sendMsg($number, 'inquiry', env('whatsapp_template_language'), $body_params);

                    
                    
                    //return $this->sendMsg($number, 'book_success', env('whatsapp_template_language'));
                }



            // if(!isset($extra_date->doctor_name) && !isset($extra_date->patient_name) && !isset($extra_date->patient_email) && !isset($extra_date->patient_number) && !isset($extra_date->appointment_id)){

            //     $doctor = User::where('role', 0)->where('first_name', 'LIKE', '%'.$message.'%')->orWhere('last_name', 'LIKE', '%'.$message.'%')->get()->first();

            //     //if the user write a wrong doctor name.
            //     if(!$doctor){
            //         $note = env('whatsapp_template_language') == 'en' ? 'Enter a correct doctor name' : 'أدخل أسم الدكتور بشكل صحيح';

            //         array_push($body_params, $this->makeObjectHelper($note));
            //         return $this->sendMsg($number, 'note', env('whatsapp_template_language'), $body_params);


            //     }


            //     $extra_date->doctor_name    = $message;
            //     $extra_date->doctor_id      = $doctor->id;
            //     $last_message->extra_date   = json_encode($extra_date);
            //     $last_message->save();

            //     return $this->sendMsg($number, 'get_name', env('whatsapp_template_language'));
            // }elseif(isset($extra_date->doctor_name) && !isset($extra_date->patient_name) && !isset($extra_date->patient_email) && !isset($extra_date->patient_number) && !isset($extra_date->appointment_id)){
            //         $extra_date->patient_name = $message;
            //         $last_message->extra_date = json_encode($extra_date);
            //         $last_message->save();

            //         return $this->sendMsg($number, 'get_email', env('whatsapp_template_language'));
            // }elseif(isset($extra_date->doctor_name) && isset($extra_date->patient_name) && !isset($extra_date->patient_email) && !isset($extra_date->patient_number) && !isset($extra_date->appointment_id)){
            //         $extra_date->patient_email = $message;
            //         $last_message->extra_date = json_encode($extra_date);
            //         $last_message->save();
            //         return $this->sendMsg($number, 'get_number', env('whatsapp_template_language'));

            // }elseif(isset($extra_date->doctor_name) && isset($extra_date->patient_name) && isset($extra_date->patient_email) && !isset($extra_date->patient_number) && !isset($extra_date->appointment_id)){

            //         $extra_date->patient_number = $message;
            //         $last_message->extra_date = json_encode($extra_date);
            //         $last_message->save();

            //         $appointments = Appointment::with('visit', 'visit.patient')->where('doctor_id', $extra_date->doctor_id)->where('status', 0)->where('from', '>=', date('Y-m-d H:i:s'))->take(20)->get();

            //         if(count($appointments) <= 0)
            //         {
            //             $extra_date->appointment_id = 1;    //dummy
            //             $extra_date->booked = 0;    //dummy
            //             $last_message->extra_date = json_encode($extra_date);
            //             $last_message->save();
            //             return $this->sendMsg($number, 'no_appointments', env('whatsapp_template_language'));
            //         }

            //             array_push($body_params, $this->makeObjectHelper('------------'));
            //             $msg = "";
            //             foreach($appointments as $appointment){
            //                 if(env('whatsapp_template_language') == 'ar')
            //                 {
            //                     $msg .= date('d-m-Y   h:i A', strtotime($appointment->from)) .' - ' . $appointment->id . '\n';  

            //                 }else{
            //                     $msg .= $appointment->id . '- ' .  date('d-m-Y   h:i A', strtotime($appointment->from)) . '\n';
            //                 }
            //             }
            //             array_push($body_params, $this->makeObjectHelper($msg));
            //             return $this->sendMsg($number, 'available_appointments', env('whatsapp_template_language'), $body_params);



            //       // return $this->sendMsg($number, 'book_success', env('whatsapp_template_language'));

            // }elseif(isset($extra_date->doctor_name) && isset($extra_date->patient_name) && isset($extra_date->patient_email) && isset($extra_date->patient_number) && !isset($extra_date->appointment_id)){

            //     $extra_date->appointment_id = 1;
            //     $extra_date->booked = 1;
            //     $last_message->extra_date = json_encode($extra_date);
            //     $last_message->save();

            //     return $this->sendMsg($number, 'book_success', env('whatsapp_template_language'));
            // }elseif(isset($extra_date->doctor_name) && isset($extra_date->patient_name) && isset($extra_date->patient_email) && isset($extra_date->patient_number) && isset($extra_date->appointment_id) && isset($extra_date->booked) && $extra_date->booked == 1){

            //     //pass
            //   // return $this->sendMsg($number, 'only_one_book', env('whatsapp_template_language'));

            // }


            } elseif ($last_message && $last_message->operation == WhatsappBotMessage::$INQUIRY_OPERATION) {
                $extra_date = json_decode($last_message->extra_date);
                if ($extra_date == null) {
                    $extra_date = new \stdClass();
                }

                if (!isset($extra_date->patient_number)) {

                    $extra_date->patient_number = $message;
                    $last_message->extra_date = json_encode($extra_date);
                    $last_message->save();

                    $patient = $this->checkPatientWithNumber($extra_date->patient_number);
                    if (!$patient) {
                        array_push($body_params, $this->makeObjectHelper($user_name));
                        return $this->sendMsg($number, 'no_patient', env('whatsapp_template_language'), $body_params);
                    }

                    $visit = $this->retireveVisit($patient);
                    $patient_count = 0;

                    if (isset($visit)) {

                        $doctor = WhatsappBotMessage::where('number', $number)->where('operation', WhatsappBotMessage::$BOOK_OPERATION)->latest()->first();
                        $extra_data = json_decode($doctor->extra_date);
                       
                        $patient_count = Appointment::where('doctor_id',  $extra_data->doctor_id)->where('from', 'like', '%' . date('Y-m-d', strtotime($visit->appointment->from)) . '%')
                            ->where('from', '<', $visit->appointment->from)
                            ->count();

                        $patient_count += 1;

                    $current_number = Appointment::where('doctor_id', $extra_data->doctor_id)->whereHas('visit', function($q){
                            $q->where('status', 1);
                        })->where('from', 'LIKE', '%' . date('Y-m-d') . '%')->count();

                        $current_number += 1;

                        //send the information via whatsapp
                        array_push($body_params, $this->makeObjectHelper($patient->first_name . ' ' . $patient->last_name));

                        if (env('whatsapp_template_language') == 'ar')
                        $appointment = date('h:i A', strtotime($visit->appointment->from)) . '\nالتاريخ ' . date('d-m-Y ', strtotime($visit->appointment->from )) . ' \nالدور الحالي ' . $current_number; 
                        else
                        $appointment = date('h:i A', strtotime($visit->appointment->from)) . '\nDate ' . date('d-m-Y ', strtotime($visit->appointment->from)) . '\nCurrent: ' . $current_number;
                        
                        array_push($body_params, $this->makeObjectHelper($appointment));

                        array_push($body_params, $this->makeObjectHelper($patient_count));

                        $this->sendMsg($number, 'inquiry', env('whatsapp_template_language'), $body_params);
                    } else {
                        array_push($body_params, $this->makeObjectHelper($patient->first_name . ' ' . $patient->last_name));
                        $this->sendMsg($number, 'no_booking', env('whatsapp_template_language'), $body_params);
                    }
                }
            }
            elseif($last_message && $last_message->operation == WhatsappBotMessage::$REGISTER_OPERATION){
                $extra_date = json_decode($last_message->extra_date);
                if ($extra_date == null) {
                    $extra_date = new \stdClass();
                }
                
                if (!isset($extra_date->first_name)) {
                    
                    $extra_date->first_name = $message;
                    $last_message->extra_date = json_encode($extra_date);
                    $last_message->save();

                    if (env('whatsapp_template_language') == 'ar') {
                        $msg = 'ادخل اسمك الثاني';
                    } else {
                        $msg = 'enter your last name.';
                    }
                    array_push($body_params, $this->makeObjectHelper($msg));
                    return $this->sendMsg($number, 'note', env('whatsapp_template_language'), $body_params);
                    
                }elseif (isset($extra_date->first_name) && !isset($extra_date->last_name)) {
                    
                    $extra_date->last_name = $message;
                    $last_message->extra_date = json_encode($extra_date);
                    $last_message->save();


                    if (env('whatsapp_template_language') == 'ar') {
                        $msg = 'ادخل الايميل الخاص بك ';
                    } else {
                        $msg = 'enter your email address.';
                    }
                    array_push($body_params, $this->makeObjectHelper($msg));
                    return $this->sendMsg($number, 'note', env('whatsapp_template_language'), $body_params);

                }elseif (isset($extra_date->first_name) && isset($extra_date->last_name) && !isset($extra_date->email)) {
                    
                    $email_chk_user = $this->checkPatientWithEmail($message);
                    if($email_chk_user)
                    {

                        if (env('whatsapp_template_language') == 'ar') {
                            $msg = 'يرجي اختيار ايميل اخر.';
                        } else {
                            $msg = 'choose another email.';
                        }
                        array_push($body_params, $this->makeObjectHelper($msg));
                        return $this->sendMsg($number, 'note', env('whatsapp_template_language'), $body_params);


                    }
                    
                    
                    $extra_date->email = $message;
                    $last_message->extra_date = json_encode($extra_date);
                    $last_message->save();


                    
                    if (env('whatsapp_template_language') == 'ar') {
                        $msg = 'ادخل كلمة السر الخاصة بك ';
                    } else {
                        $msg = 'enter your password.';
                    }
                    array_push($body_params, $this->makeObjectHelper($msg));
                    return $this->sendMsg($number, 'note', env('whatsapp_template_language'), $body_params);

                }elseif (isset($extra_date->first_name) && isset($extra_date->last_name) && isset($extra_date->email) && !isset($extra_date->password)) {
                    
                    $extra_date->password = $message;
                    $last_message->extra_date = json_encode($extra_date);
                    $last_message->save();


                    $register = $this->patient_register($extra_date,$num );


                    
                    // if (env('whatsapp_template_language') == 'ar') {
                    //     $msg = 'شكرا لك';
                    // } else {
                    //     $msg = 'Thank you';
                    // }
                    
                    $clinic_info = ClinicInfo::first();
                    array_push($header_params, $this->makeObjectHelper($extra_date->first_name . ' ' . $extra_date->last_name));

                    array_push($body_params, $this->makeObjectHelper($clinic_info->doctor_name));
                    array_push($body_params, $this->makeObjectHelper($extra_date->email));
                    array_push($body_params, $this->makeObjectHelper($extra_date->password));
                    array_push($body_params, $this->makeObjectHelper('https://'.$subdomain . '.'. env('APP_NAME') . '.com'));
                    
                    return $this->sendMsg($number, 'patient_welcome', env('whatsapp_template_language'), $body_params, $header_params);

                }

                

            }

            if ($message == 'استفسار' || $message ==  'inquiry') {
                $this->saveMsg($user_name, $number, $message, WhatsappBotMessage::$INQUIRY_OPERATION);
                return $this->sendMsg($number, 'get_number', env('whatsapp_template_language'));
            } elseif ($message == 'book' || $message == 'حجز') {


                //$this->sendMsg($number, 'get_doctor_name', env('whatsapp_template_language'));

                if (env('whatsapp_template_language') == 'ar') {
                    $msg = 'يرجي إدخال اسم الدكتور';
                } else {
                    $msg = 'enter the doctor name';
                }
                
                $doctors = User::where('role', 0)->get();
                foreach($doctors as $doctor)
                {
                    $msg .= '\n'. $doctor->first_name . ' ' . $doctor->last_name ;
                }
                array_push($body_params, $this->makeObjectHelper($msg));
                 $this->sendMsg($number, 'note', env('whatsapp_template_language'), $body_params);


                // save the operation 
                $this->saveMsg($user_name, $number, $message, WhatsappBotMessage::$BOOK_OPERATION);
            } elseif ($message == 'register' || $message == 'تسجيل'){
                if($patient)
                {
                    if (env('whatsapp_template_language') == 'ar') {
                        $msg = 'لا يمكن التسجيل مرة أخري بهذا الرقم.';
                    } else {
                        $msg = 'there\'s a patient registered with your whatsapp nubmer.';
                    }
                    array_push($body_params, $this->makeObjectHelper($msg));
                    return $this->sendMsg($number, 'note', env('whatsapp_template_language'), $body_params);
                }
                
                
                $this->saveMsg($user_name, $number, $message, WhatsappBotMessage::$REGISTER_OPERATION);
                
                    if (env('whatsapp_template_language') == 'ar') {
                        $msg = 'ادخل اسمك الأول';
                    } else {
                        $msg = 'enter your first name.';
                    }
                    array_push($body_params, $this->makeObjectHelper($msg));
                    return $this->sendMsg($number, 'note', env('whatsapp_template_language'), $body_params);

                
            } else {  //Unknown message => respond the welcome message
            
                array_push($body_params, $this->makeObjectHelper(isset($patient) ? $patient->first_name . ' ' . $patient->last_name :  $user_name));
                $this->saveMsg($user_name, $number, $message, WhatsappBotMessage::$UNKNOWN_OPERATION);
                $this->sendMsg($number, 'welcome', 'ar', $body_params);
            }
        }
        
        
    }

    private function sendMsg($to, $template_name, $template_lang = 'en',  $body_params = [], $header_params = [])
    {
        Http::withBody('{ "messaging_product": "whatsapp", "to": "' . $to . '", "type": "template", "template": { "name": "' . $template_name . '", "language": { "code": "' . $template_lang . '" },
              "components":[
                   {
                        "type": "header",
                        "parameters": ' . json_encode($header_params) . '
                    },
                    {
                        "type": "body",
                        "parameters": ' . json_encode($body_params) . '
                    }
                ]
                  } }', 'application/json')
            ->withToken(env('temp_whatsToken'))
            ->post(env('whatsApp_link'));
    }

    private function makeObjectHelper($parameter)
    {
        $obj = new \stdClass();
        $obj->type = 'text';
        $obj->text = $parameter;
        return $obj;
    }

    private function saveMsg($user_name, $number, $message, $status, $extra = null)
    {
        WhatsappBotMessage::create([
            'profile_name'  => $user_name,
            'number'    => $number,
            'message'   => $message,
            'operation' => $status,
            'extra_data' => $extra,
        ]);
    }

    private function checkPatientWithNumber($number)
    {
        $patient = User::where('role', 2)->where('phone', $number)->first();
        return $patient;
    }
    
    private function checkPatientWithEmail($email)
    {
        $user = User::where('email', $email)->first();
        return $user;
    }

    private function retireveVisit($patient)
    {
        $visit = Visit::with('patient:id,first_name,last_name,email,phone,image', 'appointment:id,from,to,status', 'products')
            ->whereHas('appointment', function ($query) {
                //$query->whereDate('appointments.from', date('Y-m-d'));
                $query->where('appointments.from', '>=', date('Y-m-d'));
                //$query->whereDate('appointments.to', date('Y-m-d'));
            })->where('user_id', $patient->id)->first();

        return $visit;
    }

    private function checkOnlyOneAppointment($patient, $appointment)
    {
        $from_date = date($appointment->from);
        $to_date = date($appointment->to);
        $from_date_timestamp = strtotime($from_date);
        $to_date_timestamp = strtotime($to_date);
        $from_date = date('Y-m-d', $from_date_timestamp);
        $to_date = date('Y-m-d', $to_date_timestamp);

        $patientResult = Visit::with('patient:id,first_name,last_name,email,phone,image', 'appointment:id,from,to,status')
            ->where('user_id', $patient->id)
            ->whereHas('appointment', function ($query) use ($from_date, $to_date) {
                $query->whereDate('appointments.from', $from_date);
                $query->whereDate('appointments.to', $to_date);
            })->get();
        if ($patientResult->count() > 0)
            return true;

        return false;
    }
    
    
    private function sendInfoMsg($visit, $patient )
    {
        $body_params = [];
        $body_params_obj = new \stdClass();
    }
    
    private function patient_register($extra_data, $phone)
    {
        $data = User::create([
            'first_name'    => $extra_data->first_name,
            'last_name'     => $extra_data->last_name,
            'email'         => $extra_data->email,
            'password'      => Hash::make($extra_data->password),
            'phone'         => $phone,
            'image'         => "",
            'role'          => 2,
            'status'        => 1,
        ]);
        $data->patient_info()->create([
            // 'address'   => $request->address,
            // 'date'  => $request->date,
             'weight'    => 60,
            'generated_id'  => $data->id . uniqid(),
        ]);
        
        return $data;

    }
    
    
}
