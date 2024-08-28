<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\GeneralTrait;
use App\Models\Appointment;
use App\Models\ClinicInfo;
use App\Models\patientInfo;
use App\Models\Visit;
use App\Models\User;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\Inventory\Product;
use Illuminate\Support\Facades\DB;
use App\Models\Patient\PatientPyament;
use App\Models\DoctorPaymentConf;
use App\Models\Inventory\ProductQnty;
use App\Models\VisitProduct;
use App\Services\TwilioService;
use Twilio\Rest\Client;

class VisitController extends Controller
{
    use GeneralTrait;

    // protected $twilioService;
    // // public function __construct(TwilioService $twilioService)
    // // {
    // //     $this->twilioService = $twilioService;
    // // }
    public function list()
    {
        //return response()->json(Carbon::today());
        $data = Visit::with('patient:id,first_name,last_name,email,phone,image', 'appointment:id,from,to,status,doctor_id', 'products')->get();
        // $item->clinic_address   = $clinic_address;
        // $item->clinic_image     = $clinic_image;
        // $item->doctor_name      = $doctor_name;
        // $item->doctor_phone     = $doctor_phone;

        // if ($item->status == 0) {
        //     //main amount paid means the patient paid the main amount online.
        //     if ($item->main_amount_paid == 1) {
        //         $item->amount = 0;
        //     } else {
        //         //get the amount based on the type of visit and per doctor
        //         $item->amount = User::getAmount($item->appointment->doctor_id, $item->type);
        //     }

        return $this->returnData(__('general.found_success'), 'data', $data);
    }


    public function save(Request $request)
    {
        $rules = [
            'appointment_id'    => 'required',
            // 'file'              => 'mimes:png,jpg,jpeg|max:2048'
        ];
        $validator = $this->validateRequest($request, $rules);
        if ($validator->fails())
            return $this->returnErrorResponse(__('general.validate_error'), $validator);

        $appointment = Appointment::find($request->appointment_id);
        if (!$appointment)
            return $this->returnErrorResponse(__('general.found_error'));

        $has_visit = $appointment->visit;
        if ($has_visit)
            return $this->returnErrorResponse(__('general.appointment_error'));


        //get the authenticated user.
        $user = Auth::guard('api')->user();



        /*
            if the authenticated user is admin or assistant
            check the patient id.
            else the authenticated user would be the patient.
        */
        if ($user->role == 1 || $user->role == 0) {
            $patient = User::find($request->patient_id);
            if (!$patient)
                return $this->returnErrorResponse(__('general.found_error'));
        } else {
            $patient = $user;
        }


        //patient can only book one appointment a day
        if ($this->checkOnlyOneAppointment($patient, $appointment))
            return $this->returnErrorResponse(__('general.patient_only_one_appointment'));
        //end checking only one appointment



        //End update appointment

        //online payment can only be done by role patient
        if (auth()->user()->role == 2) {

            if ($request->payMethod != 'cash') {
                //get the doctor payment configuration
                $doc_conf = DoctorPaymentConf::first();

                //check if the doctor update the his/her payment information.
                if ($doc_conf->updated_at == null) {
                    return $this->returnErrorResponse(__('general.error_happend'));
                }

                //save
                $visit = $this->saveVisistHelper($patient, $appointment, $request->type);

                //update appointment to be unavailable
                $appointment->status = 1;
                $appointment->save();

                $request->visit_id = $visit->id;
                return $this->payPatient($request->route('subdomain'), $request, $visit);
            }
        }
        //save
        $visit = $this->saveVisistHelper($patient, $appointment, $request->type);

        //update appointment to be unavailable
        $appointment->status = 1;
        $appointment->save();

        //view the appointment for specific visit
        $visit->appointment;

        return $this->returnSuccessResponse(__('general.add_success'), $visit);
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


    private function saveVisistHelper($user, $appointment, $type = 'not')
    {
        $doctorId = Auth::guard('api')->user()->id;

        $visit = Visit::create([
            'user_id'           => $user->id,
            'appointment_id'   => $appointment->id,
            'type'   => $type,
            'doctor_id' => $doctorId,
        ]);

        //  $this->sendWhatsAppMessageNewBooking($user, $appointment);
        // $response = json_decode($response);
        // if(isset($response) && isset($response->error))
        //     return $this->returnErrorResponse('Invalid Number');



        return $visit;
    }

    private function sendWhatsAppMessageNewBooking($user, $appointment)
    {
        $lang = $this->returnLocaleLanguage();
        // to get the patient turn.
        $patient_count = Appointment::where('from', 'like', '%' . date('Y-m-d', strtotime($appointment->from)) . '%')
            ->where('from', '<', $appointment->from)
            ->count();

        $temp_from = new DateTime($appointment->from);
        $from = $temp_from->format('h:i a m/d/Y');

        $temp_to = new DateTime($appointment->to);
        $to = $temp_to->format('h:i a m/d/Y');
        //$to = date('h:i:s a m/d/Y', strtotime($appointment->to));

        $patient_count += 1;

        $response = Http::withBody('
                {
            "messaging_product": "whatsapp",
            "to": "+2' . $user->phone . '",
            "type": "template",
            "template": {
                "name": "patient_book",
                "language": {
                    "code": "' . $lang . '"
                },
                "components": [
                    {
                        "type": "body",
                        "parameters": [
                            {
                                "type": "text",
                                "text": "' . $user->first_name . ' ' . $user->last_name . '"
                            },
                            {
                                "type": "text",
                                "text": "' . $from . ' to  ' . $to . '"
                            },
                            {
                                "type": "text",
                                "text": "' . $patient_count  . '"
                            }
                        ]
                    }
                ]
            }
        }
            ', 'application/json')
            ->withToken(env('WHATSAPP_API_KEY'))
            ->post('https://graph.facebook.com/v14.0/111701565072005/messages');
    }

    //     public function update($subdomain,$id, Request $request)
    //     {
    //         $rules = [
    //             'appointment_id'        => 'required',
    //             'files.*'               => 'mimes:png,jpg,jpeg|max:2048',

    //         ];

    //         $validator = $this->validateRequest($request, $rules);
    //         if ($validator->fails())
    //             return $this->returnErrorResponse(__('general.validate_errro'), $validator);


    //           $visit = Visit::find($id);
    //         //  return response()->json($visit);

    //         if (!$visit)
    //             return $this->returnErrorResponse(__('general.found_error'));

    //         if ($request->status) {
    //             $visit->status          = $request->status;
    //             //$visit->type            = $request->type;
    //             $visit->amount          = $request->amount;
    //             // $visit->extra_amount    = ($request->extra_amount)?$request->extra_amount:0 ;
    //         }

    //         if ($request->description)
    //             $visit->description          = $request->description;
    //         // else
    //         //     $visit->description          = '';


    //         if ($request->hasFile('files')) {

    //             $visit->file = $this->UploadMultipleImages($request, 'users/patients/files', 'files');
    //         }


    //         // update products with quantaties
    //          $productIds = $request->input('products.*.product_id');
    //          $productQuantities = $request->input('products.*.product_quantity');

    //         // update services with quantaties
    //         $serviceIds = $request->input('services.*.service_id');
    //         $serviceQuantities = $request->input('services.*.service_quantity');

    //         // first process-update products with quantaties
    //         if ($productIds && count($productIds) === count($productQuantities)) {
    //             foreach ($productIds as $index => $productId) {
    //                  $product = Product::find($productId);
    //                 if (!$product) {
    //                     return $this->returnErrorResponse(__('general.found_error'));
    //                 }
    //                 $visitProduct =  VisitProduct::where('visit_id', $visit->id)->where('product_id', $productId)->first();
    //                 $productQuantity = $productQuantities[$index];

    //                 if ($visitProduct) {
    //                      $currentQuantity = $visitProduct->quantity;
    //                     $newQuantity = $currentQuantity + $productQuantity;
    //                     $visitProduct->update([
    //                         'quantity' => $newQuantity,
    //                     ]);
    //                 } else {
    //                     VisitProduct::create([
    //                         'visit_id' => $visit->id,
    //                         'product_id' => $productId,
    //                         'quantity' => $productQuantity,
    //                     ]);
    //                 }
    //                 $currentProductQuantity = $product->qnty;  //get current quantity

    //                 $newProductQuantity = $currentProductQuantity - $productQuantity;   //Subtract the new quantity from the existing quantity

    //                 $newProductQuantity = max(0, $newProductQuantity);    //new quantity must be not less than zero


    //                 // update quantity for this product
    //                 Product::where('id', $productId)->update([
    //                     'qnty' => $newProductQuantity,                 //update quantity for this product
    //                 ]);


    //                 //send alert message when the product quantity less than or equal product of alert quantity

    //                 if ($product->type != 'device') {
    //                     //$product->qnty = $product->qnty - $arry_counts[$p_id];

    //                     if ($product->qnty <= $product->alert_qty) {
    //                         return response()->json(['message'=>" the quantity inserted exceeded the specified quantity"]);
    //                         // $this->sendWhatsAppMessage($product);
    //                         // $doctor_phone = ClinicInfo::first()->doctor_phone;

    //                     }
    //                 }
    //             }




    //             $arr_to_insert = [];

    //             //get the old extra amount.
    //             //$extra = $visit->extra_amount;
    //             $extra = 0; //$visit->extra_amount;
    //             foreach ($productIds as $p_id) {
    //                 $product->save();
    //                 $arr_to_insert[] = ['visit_id' => $visit->id, 'product_id' => $p_id];
    //             }
    //         }
    //         // update services with quantaties
    //         if ($serviceIds && count($serviceIds) === count($serviceQuantities)) {
    //             foreach ($serviceIds as $index => $serviceId) {
    //                 $service = Product::find($serviceId);
    //                 if (!$service) {
    //                     return $this->returnErrorResponse(__('general.found_error'));
    //                 }
    //                 $visitService =  VisitProduct::where('visit_id', $visit->id)->where('product_id', $serviceId)->first();

    //                 $serviceQuantity = $serviceQuantities[$index];
    //                 if ($visitService) {
    //                     $currentQuantity = $visitService->quantity;

    //                     $newQuantity = $currentQuantity + $productQuantity;
    //                     $visitService->update([
    //                         'quantity' => $newQuantity,
    //                     ]);
    //                 } else {
    //                     VisitProduct::create([
    //                         'visit_id' => $visit->id,
    //                         'product_id' => $serviceId,
    //                         'quantity' => $serviceQuantity,
    //                     ]);
    //                 }


    //                 $currentServiceQuantity = $service->qnty;  //get current quantity

    //                 $newServiceQuantity = $currentServiceQuantity - $serviceQuantity;   //Subtract the new quantity from the existing quantity

    //                 $newServiceQuantity = max(0, $newServiceQuantity);    //new quantity must be not less than zero


    //                 // update quantity for this product
    //                 Product::where('id', $serviceId)->update([
    //                     'qnty' => $newServiceQuantity,                 //update quantity for this product
    //                 ]);
    //                 if ($product->type != 'device') {
    //                     if ($service->qnty <= $service->alert_qty) {
    //                         $doctor_phone = ClinicInfo::first()->doctor_phone;
    //                     //   $this->sendWhatsAppMessage($service);

    //                           }
    //                 }




    //             $arr_to_insert = [];

    //             //get the old extra amount.
    //             //$extra = $visit->extra_amount;
    //             $extra = 0; //$visit->extra_amount;
    //             foreach ($serviceIds as $p_id) {
    //                 $product = null;
    //                  $product = Product::find($p_id);
    //                 //append the price of products to extra amount.
    //                 $extra += $product->price;
    //                 //save the updated product
    //                 $product->save();
    //                 $arr_to_insert[] = ['visit_id' => $visit->id, 'product_id' => $p_id];
    //             }
    //         }





    //         $visit->save();
    //           $visit = Visit::find($id);

    //         $clinic_info            = ClinicInfo::first();
    //         $amount                 = $clinic_info->amount;
    //         $follow_amount    = $clinic_info->consultation_amount;



    //         $visit->appointment;
    //         $visit->amount = ($visit->type == 'follow') ? $follow_amount : $amount;


    //         $visit->patient;
    //         $visit->products;

    //         return $this->returnSuccessResponse(__('general.edit_success'), $visit);
    //     }
    // }

    public function update($subdomain, $id, Request $request)
    {
        // return $request;
        $rules = [
            'appointment_id'        => 'required',
            'files.*'               => 'mimes:png,jpg,jpeg|max:2048',

        ];

        $validator = $this->validateRequest($request, $rules);
        if ($validator->fails())
            return $this->returnErrorResponse(__('general.validate_errro'), $validator);


        $visit = Visit::find($id);
        //  return response()->json($visit);

        if (!$visit)
            return $this->returnErrorResponse(__('general.found_error'));

        if ($request->status) {
            $visit->status          = $request->status;
            //$visit->type            = $request->type;
            $visit->amount          = $request->amount;
            // $visit->extra_amount    = ($request->extra_amount)?$request->extra_amount:0 ;
        }

        if ($request->description)
            $visit->description          = $request->description;
        // else
        //     $visit->description          = '';


        if ($request->hasFile('files')) {

            $visit->file = $this->UploadMultipleImages($request, 'users/patients/files', 'files');
        }


        // update products with quantaties
        $productIds = $request->input('products.*.product_id');
        $productQuantities = $request->input('products.*.product_quantity');

        // update services with quantaties
        $serviceIds = $request->input('services.*.service_id');
        $serviceQuantities = $request->input('services.*.service_quantity');
        if ($productIds) {
            // first process-update products with quantaties
            if ($productIds && count($productIds) === count($productQuantities)) {
                foreach ($productIds as $index => $productId) {
                    $product = Product::find($productId);
                    if (!$product) {
                        return $this->returnErrorResponse(__('general.found_error'));
                    }
                    $visitProduct =  VisitProduct::where('visit_id', $visit->id)->where('product_id', $productId)->first();
                    $productQuantity = $productQuantities[$index];

                    if ($product->qnty < $productQuantity) {
                        // return [
                        //     'message' => "There are $product->qnty in stock only",
                        //     'status' => false
                        // ];
                        return $this->returnErrorResponse("There are $product->qnty in stock only");
                    }

                    if ($visitProduct) {
                        $currentQuantity = $visitProduct->quantity;
                        $newQuantity = $currentQuantity + $productQuantity;
                        $visitProduct->update([
                            'quantity' => $newQuantity,
                        ]);
                    } else {
                        VisitProduct::create([
                            'visit_id' => $visit->id,
                            'product_id' => $productId,
                            'quantity' => $productQuantity,
                        ]);
                    }
                    $currentProductQuantity = $product->qnty;  //get current quantity

                    $newProductQuantity = $currentProductQuantity - $productQuantity;   //Subtract the new quantity from the existing quantity

                    $newProductQuantity = max(0, $newProductQuantity);    //new quantity must be not less than zero


                    // update quantity for this product
                    Product::where('id', $productId)->update([
                        'qnty' => $newProductQuantity,                 //update quantity for this product
                    ]);


                    //send alert message when the product quantity less than or equal product of alert quantity

                    if ($product->type != 'device') {
                        //$product->qnty = $product->qnty - $arry_counts[$p_id];

                        if ($product->qnty <= $product->alert_qty) {

                            // $this->sendWhatsAppMessage($product);
                            // $doctor_phone = ClinicInfo::first()->doctor_phone;

                        }
                    }
                }




                $arr_to_insert = [];

                //get the old extra amount.
                //$extra = $visit->extra_amount;
                $extra = 0; //$visit->extra_amount;
                foreach ($productIds as $p_id) {
                    $product->save();
                    $arr_to_insert[] = ['visit_id' => $visit->id, 'product_id' => $p_id];
                }
            }
            // update services with quantaties
        }


        if ($serviceIds) {
            if ($serviceIds && count($serviceIds) === count($serviceQuantities)) {
                foreach ($serviceIds as $index => $serviceId) {
                    $service = Product::find($serviceId);
                    if (!$service) {
                        return $this->returnErrorResponse(__('general.found_error'));
                    }
                    $visitService =  VisitProduct::where('visit_id', $visit->id)->where('product_id', $serviceId)->first();

                    $serviceQuantity = $serviceQuantities[$index];
                    if ($visitService) {
                        $currentQuantity = $visitService->quantity;

                        $newQuantity = $currentQuantity + $serviceQuantity;
                        $visitService->update([
                            'quantity' => $newQuantity,
                        ]);
                    } else {
                        VisitProduct::create([
                            'visit_id' => $visit->id,
                            'product_id' => $serviceId,
                            'quantity' => $serviceQuantity,
                        ]);
                    }


                    $currentServiceQuantity = $service->qnty;  //get current quantity

                    $newServiceQuantity = $currentServiceQuantity - $serviceQuantity;   //Subtract the new quantity from the existing quantity

                    $newServiceQuantity = max(0, $newServiceQuantity);    //new quantity must be not less than zero


                    // update quantity for this product
                    Product::where('id', $serviceId)->update([
                        'qnty' => $newServiceQuantity,                 //update quantity for this product
                    ]);
                    if ($service->type != 'device') {
                        if ($service->qnty <= $service->alert_qty) {
                            $doctor_phone = ClinicInfo::first()->doctor_phone;
                            //   $this->sendWhatsAppMessage($service);

                        }
                    }


                    $arr_to_insert = [];

                    //get the old extra amount.
                    //$extra = $visit->extra_amount;
                    $extra = 0; //$visit->extra_amount;
                    foreach ($serviceIds as $p_id) {
                        $product = null;
                        $product = Product::find($p_id);
                        //append the price of products to extra amount.
                        $extra += $product->price;
                        //save the updated product
                        $product->save();
                        $arr_to_insert[] = ['visit_id' => $visit->id, 'product_id' => $p_id];
                    }
                }
            }
        }


        $visit->save();
        $visit = Visit::find($id);

        $clinic_info            = ClinicInfo::first();
        $amount                 = $clinic_info->amount;
        $follow_amount    = $clinic_info->consultation_amount;



        $visit->appointment;
        $visit->amount = ($visit->type == 'follow') ? $follow_amount : $amount;


        $visit->patient;
        $visit->products;

        return $this->returnSuccessResponse(__('general.edit_success'), $visit);
    }
    public function delete($subdomain, $id)
    {
        $data = Visit::find($id);
        if (!$data)
            return $this->returnErrorResponse(__('general.found_error'));

        $appointment = Appointment::find($data->appointment_id);
        if (!$appointment)
            return $this->returnErrorResponse(__('general.found_error'));

        $authenticated_user = Auth::guard('api')->user();

        $data_time = $data->created_at;
        $current_time = (new \DateTime())->format('Y-m-d H:i:s');
        $interval = abs(strtotime($data_time) - strtotime($current_time));

        //authenticated user is patient and the visit must be created in less than 1 hour.
        if ($authenticated_user->role == 2 && $interval >= 3600) {
            //patient can't delete visit for other users.
            if ($data->user_id != $authenticated_user->id)
                return $this->returnErrorResponse(__('general.unAuthenticated'));

            return $this->returnErrorResponse(__('general.CannotCancelVisit'));
        }

        $appointment->status = 0;
        $appointment->save();

        $data->delete();

        return $this->returnSuccessResponse(__('general.delete_success'));
    }

    // public function searchByGeneratedId($generated_id)
    // {
    //     $patient_info = patientInfo::where('generated_id', $generated_id)->first();
    //     if(!$patient_info)
    //         return $this->returnErrorResponse(__('general.found_error'));

    //     $patient = $patient_info->patient;
    //     $patient->weight = $patient_info->weight;
    //     $patient->address = $patient_info->address;
    //     $patient->date = $patient_info->date;
    //     $patient->generated_id = $patient_info->generated_id;
    //     $patient->visits;
    //     return $this->returnData(__('general.found_success'), 'data',$patient );
    // }

    public function getById($subdomain, $id)
    {
        $data = Visit::find($id);
        if (!$data)
            return $this->returnErrorResponse(__('general.found_error'));

        $data->appointment;
        $data->patient;

        if ($data->patient->patient_info) {
            $data->patient->weight = $data->patient->patient_info->weight;
            $data->patient->address = $data->patient->patient_info->address;
            $data->patient->date = $data->patient->patient_info->date;
            $data->patient->generated_id = $data->patient->patient_info->generated_id;
            unset($data->patient->patient_info);
        }
        return $this->returnData(__('general.found_success'), 'data', $data);
    }

    public function getVisitswithPayment()
    {
        $data = Visit::with('patient:id,first_name,last_name,email,phone,image', 'appointment:id,from,to,status')
            ->where('status', 1)
            // ->whereHas('appointment', function($query) {
            //     $query->whereDate('appointments.from',date('Y-m-d'));
            //     $query->whereDate('appointments.to', date('Y-m-d'));
            //})
            ->paginate(10);

        return $this->returnData(__('general.found_success'), 'data', $data);
    }

    public function VisistsInRange(Request $request)
    {

        //'2018-01-01'
        $rules = [
            'from'  => 'required',
            'to'  => 'required',
        ];
        $validator = $this->validateRequest($request, $rules);
        if ($validator->fails())
            return $this->returnErrorResponse(__('general.validate_error'), $validator);

        $from = date($request->from);
        $to = date($request->to);

        $obj = new \stdClass();
        $data = Visit::with('patient:id,first_name,last_name,email,phone,image', 'appointment:id,from,to,status')
            ->where('status', 1)
            ->whereHas('appointment', function ($query) use ($from, $to) {
                $query->whereDate('appointments.from', '>=', $from);
                $query->whereDate('appointments.to', '<=', $to);
            })->paginate(10);
        $obj->visits_data = $data;
        $obj->products_data = ProductQnty::with('product:id,name')->where('created_at', '>=', $from)->where('created_at', '<=', $to)->get();

        return $this->returnData(__('general.found_success'), 'data', $data);
    }


    public function getPrescriptionsByPatient($subdomain, $patient_id)
    {
        $patient = User::where('id', $patient_id)->where('role', 2)->get()->first();
        if (!$patient)
            return $this->returnErrorResponse(__('general.found_error'));


        $lang = $this->returnLocaleLanguage();
        $clinic_info = ClinicInfo::select(
            'clinic_name_' . $lang . ' as clinic_name',
            'clinic_image',
            'doctor_name',
            'clinic_address_' . $lang . ' as clinic_address',
            'doctor_phone'
        )->first();
        $obj = new \stdClass();
        $obj->clinic_info = $clinic_info;


        //return today's prescriptions or the the previous not null prescriptions.
        $data = Visit::select('id', 'user_id', 'appointment_id', 'description')->where('user_id', $patient_id)->with('appointment')
            ->where(function ($query) {
                $query->whereHas('appointment', function ($query) {
                    $query->whereDate('from', Carbon::today());
                });
            })
            ->orWhere(function ($query) {

                $query->where('description', '<>', '');
            })
            ->latest()->paginate(10);
        $obj->data = $data;
        return $this->returnData(__('general.found_success'), 'data', $obj);
    }
    public function getDocumentsByPatient($subdomain,   $patient_id)
    {
        $patient = User::where('id', $patient_id)->where('role', 2)->get()->first();
        if (!$patient)
            return $this->returnErrorResponse(__('general.found_error'));

        //return today's files or the the previous not null files.
        $data = Visit::select('id', 'user_id', 'appointment_id', 'file')->where('user_id', $patient_id)->with('appointment')
            ->where(function ($query) {
                $query->whereHas('appointment', function ($query) {
                    $query->whereDate('from', Carbon::today());
                });
            })->orWhere(function ($query) {

                $query->whereNotNull('file');
            })
            ->latest()->paginate(10);
        return $this->returnData(__('general.found_success'), 'data', $data);
    }

    public function editDescription(Request $request)
    {
        $rules = [
            'description'  => 'required',
            'id'  => 'required',
        ];
        $validator = $this->validateRequest($request, $rules);
        if ($validator->fails())
            return $this->returnErrorResponse(__('general.validate_error'), $validator);

        $data = Visit::find($request->id);
        if (!$data)
            return $this->returnErrorResponse(__('general.found_error'));

        $data->description = $request->description;
        $data->save();

        $data->appointment;
        $data->appointment->doctor;
        return $this->returnSuccessResponse(__('general.edit_success'), $data);
    }
    public function removeFileFromVisit(Request $request)
    {
        $rules = [
            'file_path'   => 'required',
            'id'            => 'required',
        ];
        $validator = $this->validateRequest($request, $rules);
        if ($validator->fails())
            return $this->returnErrorResponse(__('general.validate_error'), $validator);

        $data = Visit::find($request->id);
        if (!$data)
            return $this->returnErrorResponse(__('general.found_error'));

        $file = json_decode($data['file']);
        $new_arr = [];
        if (is_array($file)) {
            foreach ($file as $f) {
                if ($f != $request->file_path) {
                    $new_arr[] = $f;
                }
            }
        }

        $data->file = json_encode($new_arr);
        $data->save();

        $data->appointment;
        $data->appointment->doctor;

        return $this->returnSuccessResponse(__('general.edit_success'), $data);
    }

    //update files.
    public function addFileToVisit(Request $request)
    {
        $rules = [
            'image'         => 'mimes:png,jpg,jpeg|max:2048',
            'id'            => 'required',
        ];
        $validator = $this->validateRequest($request, $rules);
        if ($validator->fails())
            return $this->returnErrorResponse(__('general.validate_error'), $validator);

        $data = Visit::find($request->id);
        if (!$data)
            return $this->returnErrorResponse(__('general.found_error'));

        //$files = json_decode($data['file']);
        if ($request->hasFile('files')) {
            $data->file = $this->UploadMultipleImages($request, 'users/patients/files', 'files');
        }

        $data->save();

        $data->appointment;
        $data->appointment->doctor;

        return $this->returnSuccessResponse(__('general.edit_success'), $data);
    }

    public function addSingleFileToVisit(Request $request)
    {
        $rules = [
            'image'         => 'mimes:png,jpg,jpeg|max:2048',
            'id'            => 'required',
        ];
        $validator = $this->validateRequest($request, $rules);
        if ($validator->fails())
            return $this->returnErrorResponse(__('general.validate_error'), $validator);

        $data = Visit::find($request->id);
        if (!$data)
            return $this->returnErrorResponse(__('general.found_error'));

        $files = json_decode($data['file']);
        if ($request->hasFile('file')) {
            $files[] = $this->UploadImage($request, 'users/patients/files', 'file');
        }

        $data->file = json_encode($files);

        $data->save();

        $data->appointment;
        $data->appointment->doctor;

        return $this->returnSuccessResponse(__('general.edit_success'), $data);
    }

    public function getVisitsWithPrdoucts($subdomain, $patient_id)
    {

        $patient = User::where('id', $patient_id)->where('role', 2)->first();
        if (!$patient) {
            return $this->returnErrorResponse(__('general.found_error'));
        }

        $lang = $this->returnLocaleLanguage();

        $clinic_info = ClinicInfo::first();
        $clinic_image = $clinic_info->clinic_image;
        $clinic_name = ($lang == 'ar') ? $clinic_info->clinic_name_ar : $clinic_info->clinic_name_en;
        $clinic_address = ($lang == 'ar') ? $clinic_info->clinic_address_ar : $clinic_info->clinic_address_en;

        $data = tap(
            Visit::select('id', 'user_id', 'type', 'appointment_id', 'amount', 'extra_amount', 'main_amount_paid', 'created_at')
                ->where('user_id', $patient_id)
                ->with(['products' => function ($query) {
                    $query->select(
                        'products.id',
                        'products.name',
                        'products.type',
                        'products.price',
                        'products.description',
                        'products.qnty',
                        'products.category_id',
                        'products.products_info_qty',
                        'products.alert_qty',
                        'visit_products.quantity',        //get the quantity of productus from pivot table product_visits
                    );
                }])
                ->with('appointment.visit')
                ->latest()
                ->paginate(10)
        )->map(function ($item) use ($clinic_name, $clinic_address, $clinic_image) {
            $item->clinic_name = $clinic_name;
            $item->clinic_address = $clinic_address;
            $item->clinic_image = $clinic_image;
            $item->invoice_date = date("Y-m-d", strtotime($item->created_at));
            if ($item->status == 0) {
                if ($item->main_amount_paid == 1) {
                    $item->amount = 0;
                } else {
                    // if ($item->type == 'follow')
                    //      $item->amount = $consultation_amount;
                    // else
                    //     $item->amount = $amount;

                    //get the amount based on the type of visit and per doctor
                    $item->amount = User::getAmount($item->appointment->doctor_id, $item->type);
                }
            }
        });
        return $this->returnData(__('general.found_success'), 'data', $data);
    }

    public function callbackPatient(Request $request)
    {
        //call after patient pay for visit.


        //return response()->json($request->all());
        $conf = DoctorPaymentConf::first();
        if (!$conf)
            return $this->returnErrorResponse(__('general.found_error'));

        $data = $request->all();
        ksort($data);
        $hmac = $data['hmac'];
        $array = [
            'amount_cents',
            'created_at',
            'currency',
            'error_occured',
            'has_parent_transaction',
            'id',
            'integration_id',
            'is_3d_secure',
            'is_auth',
            'is_capture',
            'is_refunded',
            'is_standalone_payment',
            'is_voided',
            'order',
            'owner',
            'pending',
            'source_data_pan',
            'source_data_sub_type',
            'source_data_type',
            'success',
        ];
        $connectedString = '';
        foreach ($data as $key => $element) {
            if (in_array($key, $array)) {
                $connectedString .= $element;
            }
        }
        $secret = $conf->hmac; //env('PAYMOB_HMAC');
        $hased = hash_hmac('sha512', $connectedString, $secret);
        if ($hased == $hmac) {

            if ($request->success == "false") {
                PatientPyament::where('order_id', $request->order)->update(['status' => 'error']);
                return $this->returnErrorResponse(__('general.paymentError'));
            }

            PatientPyament::where('order_id', $request->order)->update(['status' => 'success']);
            $order = PatientPyament::where('order_id', $request->order)->first();
            if (!$order)
                return $this->returnErrorResponse(__('general.found_error'));

            //paid status need to be updated.
            $visit = Visit::find($order->visit_id);
            if (!$visit)
                return $this->returnErrorResponse(__('general.found_error'));

            //update the visits as intial amount paid.
            $visit->main_amount_paid = 1;
            $visit->save();

            $url = 'https://' . $request->route('subdomain') . '.ayadty.com/appointments?PaymentSuccess=S101';
            return \Redirect::away($url);
        }
    }
    // get total amount for patients based on date
    public function getTotalAmountForPatients(Request $request)
    {


        //'2018-01-01'
        $rules = [
            'from'  => 'required',
            'to'  => 'required',
        ];
        $validator = $this->validateRequest($request, $rules);
        if ($validator->fails())
            return $this->returnErrorResponse(__('general.validate_error'), $validator);

        $from = date($request->from);
        $to = date($request->to);

        $obj = new \stdClass();
        $data = Visit::where('status', 1)
            ->whereHas('appointment', function ($query) use ($from, $to) {
                $query->whereDate('appointments.from', '>=', $from);
                $query->whereDate('appointments.to', '<=', $to);
            })
            ->select("appointment_id", DB::raw("SUM(amount + extra_amount) as total"))
            ->groupBy("appointment_id")
            ->get();
        $obj->visits_data = $data;
        $obj->products_data = ProductQnty::with('product:id,name')->where('created_at', '>=', $from)->where('created_at', '<=', $to)->get();

        return $this->returnData(__('general.found_success'), 'data', $data);
    }


    // public function sendSMS()
    // {
    //     $phone = '+20 10 09265348';
    //     $message = "the product quantity is very low";
    //     $response = $this->twilioService->sendSMS($phone, $message);
    //     if ($response->sid) {
    //         return response()->json(['success' => true, 'message' => 'SMS sent successfully.']);
    //     } else {

    //         return response()->json(['success' => false, 'message' => 'Failed to send SMS.']);
    //     }
    // }

    // private function sendWhatsAppMessage($product)
    // {
    //     $twilioSid = env('TWILIO_SID');
    //     $twilioToken = env('TWILIO_AUTH_TOKEN');
    //     $twilioWhatsAppNumber = env('TWILIO_WHATSAPP_NUMBER');
    //     $number = +201009265348;
    //     $number = strval($number);
    //     $alert_qnty = $product->alert_qty;
    //     $recipientNumber = 'whatsapp:' . $number; // Replace with the recipient's phone number in WhatsApp format (e.g., "whatsapp:+1234567890")
    //     $message='Note that your product with name: ' . $product->name . ' exceeded the specified quantity which is: ' . $alert_qnty;

    //     $twilio = new Client($twilioSid, $twilioToken);

    //     try {
    //         $twilio->messages->create(
    //             $recipientNumber,
    //             [
    //                 "from" => $twilioWhatsAppNumber,
    //                 "body" => $message,
    //             ]
    //         );

    //         return response()->json(['message' => 'WhatsApp message sent successfully']);
    //     } catch (\Exception $e) {
    //         return response()->json(['error' => $e->getMessage()], 500);
    //     }
    // }


    public function updateVisit($subdomain, $id, Request $request)
    {
        // return $request;

        $visit = Visit::find($id);

        if (!$visit) {
            // Handle case where Visit with the given ID does not exist
            return response()->json(['message' => 'Visit not found'], 404);
        }

        $visit->status = 1;
        $visit->amount = $request->amount;
        $visit->extra_amount = $request->extra_amount;
        $visit->save();

        return response()->json(['message' => 'Visit updated successfully'], 200);
    }
}
