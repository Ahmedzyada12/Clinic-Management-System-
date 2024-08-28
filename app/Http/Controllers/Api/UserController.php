<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\GeneralTrait;
use App\Models\Appointment;
use App\Models\ClinicInfo;
use App\Models\User;
use App\Models\Visit;
use App\Traits\Permations;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use stdClass;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactMail;



class UserController extends Controller
{
    use GeneralTrait;
    use Permations;
    public function update(Request $request)
    {
        $rules = [
            'first_name'    => 'required',
            'last_name'    => 'required',
            'email'    => 'required',
            'phone'    => 'required',
            'image' => 'mimes:png,jpg,jpeg'
        ];
        $validator = $this->validateRequest($request, $rules);
        if ($validator->fails())
            return $this->returnErrorResponse(__('general.validate_error'), $validator);

        $data =  Auth::guard('api')->user();
        $data->first_name = $request->first_name;
        $data->last_name = $request->last_name;
        $data->phone = $request->phone;
        $data->email = $request->email;


        $image = '';
        if ($request->hasFile('image'))
            $image = $this->UploadImage($request, 'users/assistants', 'image');

        $data->image = $image;
        $data->save();


        /* to retrieve the expiry date. */
        $subData = DB::connection('mysql_kyan')->table('domains')->where('subdomain', $request->route('subdomain'))->first();
        if(!$subData)
            return $this->returnErrorResponse(__('general.found_error'));


        $clinic_image = ClinicInfo::first()->clinic_image;

        $data->subdomain_expiry_date = $subData->expiry_date;
        $data->plan_days = $subData->days;
        $data->clinic_image = $clinic_image;


        return $this->returnSuccessResponse(__('general.edit_success'), $data);
    }
    public function editCredentials(Request $request)
    {
        $rules = [
            'password'          => 'required|max:255',
            'confirm_password'  => 'required|max:255',
        ];
        $validator = $this->validateRequest($request, $rules);
        if ($validator->fails())
            return $this->returnErrorResponse(__('general.validate_error'), $validator);

        if ($request->password != $request->confirm_password)
            return $this->returnErrorResponse(__('auth.password_confirm_error'));

        $user = Auth::guard('api')->user();
        $user->password = Hash::make($request->password);
        $user->save();


        return $this->returnSuccessResponse(__('general.edit_success'));
    }

    public function changeStatus(Request $request)
    {

        $user = Auth::guard('api')->user();
        $user->status = ($user->status == 1) ? 0 : 1;
        $user->save();
        return $this->returnSuccessResponse(__('general.edit_success'), $user);
    }
    public function editClinicInfo(Request $request)
    {
        $rules = [
            'clinic_name_ar'    => 'max:255',
            'clinic_name_en'    => 'max:255',
            'clinic_address_ar'   => 'max:255',
            'clinic_address_en'   => 'max:255',
            'clinic_department_ar'   => 'max:255',
            'clinic_department_en'   => 'max:255',
            'clinic_image'      => 'mimes:png,jpg,jpeg|max:2048',
            'doc_image'      => 'mimes:png,jpg,jpeg|max:2048',
            // 'logo'              => 'mimes:png,jpg,jpeg|max:2048',
            'clinic_fblink'     => 'max:255',
            'doctor_name'       => 'max:255',
            'doctor_email'      => 'max:255',
            'doctor_phone'      => 'max:255',
            'amount'      => 'max:255',
        ];

        $validator = $this->validateRequest($request, $rules);
        if($validator->fails())
            return $this->returnErrorResponse(__('general.validate_error'), $validator);

        $image = '';
        if ($request->hasFile('clinic_image'))
            $image = $this->UploadImage($request, 'users/clinic', 'clinic_image');

        $image2 = '';
        if ($request->hasFile('doc_image'))
            $image2 = $this->UploadImage($request, 'users/clinic', 'doc_image');

        $logo = '';
        if ($request->hasFile('logo'))
            $logo = $this->UploadImage($request, 'users/clinic', 'logo');
        $data = ClinicInfo::first();

        $data->clinic_name_ar       = $request->clinic_name_ar;
        $data->clinic_name_en       = $request->clinic_name_en;
        $data->clinic_bio_en        = $request->clinic_bio_en;
        $data->clinic_bio_ar        = $request->clinic_bio_ar;
        $data->clinic_address_ar    = $request->clinic_address_ar;
        $data->clinic_address_en    = $request->clinic_address_en;
        $data->clinic_department_ar = $request->clinic_department_ar;
        $data->clinic_department_en = $request->clinic_department_en;
        $data->clinic_image         = $image;
        $data->doc_image            = $image2;
        // $data->logo                 = $logo;
        $data->clinic_fblink        = $request->clinic_fblink;
        $data->doctor_name          = $request->doctor_name;
        $data->doctor_email         = $request->doctor_email;
        $data->doctor_phone         = $request->doctor_phone;
        $data->amount               = $request->amount;
        $data->consultation_amount        = $request->consultation_amount;
        $data->save();

        return $this->returnSuccessResponse(__('general.edit_success'), $data);
    }
    public function returnClinicInfo()
    {
        $lang = $this->returnLocaleLanguage();
        $data = ClinicInfo::select(
            'id',
            'clinic_name_ar',
            'clinic_name_en',
            'clinic_name_'.$lang.' as clinic_name',
            'clinic_bio_ar',
            'clinic_bio_en',
            'clinic_bio_' . $lang . ' as clinic_bio',
            'clinic_address_ar',
            'clinic_address_en',
            'clinic_address_' . $lang . ' as clinic_address',
            'clinic_department_ar',
            'clinic_department_en',
            'clinic_department_' . $lang . ' as clinic_department',
            'clinic_image',
            'doc_image',
            // 'logo',
            'clinic_fblink',
            'doctor_name',
            'doctor_email',
            'doctor_phone',
            'consultation_amount',
            'amount'
            )->first();
        return $this->returnData(__('general.found_success'), 'data', $data);
    }

    public function myProfile()
    {
        $user = Auth::guard('api')->user();
        if ($user->role == 2 &&  $user->patient_info) {
            $user = User::with('visits', 'visits.appointment')->join('patient_infos', 'users.id', 'patient_infos.user_id')
                ->select(
                    'users.id as id',
                    'first_name',
                    'last_name',
                    'email',
                    'phone',
                    'image',
                    'role',
                    'users.created_at',
                    'users.updated_at',
                    'patient_infos.weight',
                    'patient_infos.address',
                    'patient_infos.generated_id',
                    'patient_infos.id as pateint_info_id',
                )
                ->where('users.role', 2)
                ->where('users.id', $user->id)

                ->first();
        }

        return $this->returnData(__('general.found_success'), 'data', $user);
    }
    public function dashbaord()
    {
        $authenticated_user = Auth::guard('api')->user();

        $dashboard_object = new stdClass();
        //check the user wheather he is admin, assistant, or patient.
        if ($authenticated_user->role == 0) {

            $dashboard_object->pending_visits_number = Visit::where('status', 0)->count();
            $dashboard_object->finished_visits_number = Visit::where('status', 1)->count();
            $dashboard_object->patients_number = User::where('role', 2)->count();
            $dashboard_object->assistants_number = User::where('role', 1)->count();
            $dashboard_object->super_admins_number = User::where('role', 0)->count();






            /** Add Payment */
            $months = ['january', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            $result = [];
            $current_month = date("m");


            $last_main = 0;
            $last_extra = 0;

            $i = 1;
            foreach ($months as $month) {
                if ($i <= $current_month) {
                    $data = null;
                    $first_day = (new DateTime('first day of ' . $month . ' this year'))->format('Y-m-d');
                    $last_day = (new Datetime('last day of ' . $month . ' this year'))->format('Y-m-d');
                    $visits = Visit::where('status', 1)
                        ->whereDate('created_at', '>=', $first_day)
                        ->whereDate('created_at', '<=', $last_day)
                        ->get();

                    $main_amount = 0;
                    $extra_amount = 0;
                    foreach ($visits as $visit) {
                        $main_amount += $visit->amount;
                        $extra_amount += $visit->extra_amount;
                    }

                    $result[]  = $main_amount + $extra_amount;
                    $last_main = $main_amount;
                    $last_extra = $extra_amount;
                }
                $i += 1;
            }
            /** End Payment */

            $dashboard_object->payments_per_month =  $result;
            $dashboard_object->main_income =  $last_main;
            $dashboard_object->extra_income =  $last_extra;

            $dashboard_object->users = User::where('role', 1)->orWhere('role', 2)->take(8)->get();
            $dashboard_object->appointments = Appointment::with('visit', 'visit.patient')->get();
        } elseif ($authenticated_user->role == 1) {


            $dashboard_object->patients_number = User::where('role', 2)->count();
            $dashboard_object->pending_visits_number = Visit::where('status', 0)->count();
            $dashboard_object->finished_visits_number = Visit::where('status', 1)->count();

            $dashboard_object->appointments = Appointment::with('visit', 'visit.patient')->get();
            $dashboard_object->patients = User::join('patient_infos', 'users.id', 'patient_infos.user_id')
                ->select(
                    'users.id as id',
                    'first_name',
                    'last_name',
                    'email',
                    'phone',
                    'image',
                    'role',
                    'status',
                    'users.created_at',
                    'users.updated_at',
                    'patient_infos.weight',
                    'patient_infos.address',
                    'patient_infos.generated_id',
                    'patient_infos.id as pateint_info_id',
                )
                ->where('users.role', 2)
                ->take(8)->get();
        } else {
            $dashboard_object->appointments = Appointment::with('visit', 'visit.patient')->get()->map(function ($item) use ($authenticated_user) {
                if ($item->visit) {
                    //Hide the visitor information from the patient.
                    if ($item->visit->user_id != $authenticated_user->id) {
                        unset($item->visit);
                    }
                } else {
                    unset($item->visit);
                }
                return $item;
            });
        }


        return $this->returnData(__('general.found_success'), 'data', $dashboard_object);
    }


    public function getPermissions($subdomain,$id){
         $user = User::find($id); // Find the user you want to update

        $data= $user->permission;
        // $userPer= Auth::guard('sanctum')->user()->permations;
        // if(empty($userPer)){
        //     return response()->json('access denied',401);
        // }
        // elseif($userPer['sector']['create'] == 0){
        //     return response()->json('access denied',401);
        // }
        // if ($this->permation('sector', 'read') !== true) {
        //   return $this->permation('sector','read');
        // }
        if(!$data){
            return response()->json("no permissions found");
        }
        return response()->json($data);
    }

    public function updatePermissions($subdomain,Request $request,$id){
        $user = User::find($id); // Find the user you want to update
        $data=  $user->permission;
      $childArray = $request->input('child', []);
      $valueArray = $request->input('value', []);
    //  $data[$request->parent][$request->child] =$request->value;
    foreach ($childArray as $index => $child) {
        $value = $valueArray[$index];

        // Update the permissions array
        $data[$request->input('parent')][$child] = $value;
    }
    //  dd(json_encode($data));
    $user->update([
        'permission'=>json_decode(json_encode($data))
    ]);
        return response()->json(['data'=>$data,'success' => 'Permissions updated successfully.']);
    }

      public function getClinicInfo()
    {
         $lang = $this->returnLocaleLanguage();

          $data =  ClinicInfo::select([
            'id',
            'clinic_name_ar',
            'clinic_name_en',
             'clinic_name_'.$lang.' as clinic_name',
            'clinic_address_ar',
            'clinic_address_en',
            'clinic_address_' . $lang . ' as clinic_address',
            'doctor_phone'
        ])->first();
        return response()->json(['data'=>$data]);
    }
    
    public function sendMail(Request $request) {
        // return $request;
        $details = [
            'name' => $request->f_nama ." ". $request->l_name,
            'email' => $request->mail,
            'phone' => $request->phone,
            'message' => $request->message
        ];
        
        $clinic_email = User::first()->email;

        Mail::to($clinic_email)->send(new ContactMail($details, $request->subject));
        return 'successfully';
    }


}
