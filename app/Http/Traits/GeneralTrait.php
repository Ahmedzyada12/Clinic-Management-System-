<?php

namespace App\Http\Traits;

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\ClinicInfo;
use App\Models\DoctorPaymentConf;
use App\Models\Visit;
use App\Models\Patient\PatientPyament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;


trait GeneralTrait {


    public function returnErrorResponse($message='', $validator = null)
    {
        return response()->json([
            "status"                => false,
            "msg"                   => $message,
            "validation_error"      => ($validator == null) ? [] : $validator->messages()->all()
        ]);
    }
    
    

    public function returnSuccessResponse($message='', $data=null)
    {
        return response()->json([
            "status"        => true,
            "msg"           => $message,
            "data"          => $data

        ]);
    }

    public function returnData($message='', $key, $value)
    {
        
        //get the payment status from kyanlabs database.
        $db_name = Config::get('database.connections.mysql.database');
        $data = DB::connection('mysql_kyan')->table('domains')->where('db_name', $db_name)->get()->first();
        
        $payment_status = false;
        $doc_conf = DoctorPaymentConf::first();
        if( $data && $data->patient_payment == 1){
            $payment_status = true;
        }

        
        
        return response()->json([
            "status"                    => true,
            "msg"                       => $message,
            $key                        => $value,
            'payment_status'            => $payment_status,
        ]);
    }

    public function returnLocaleLanguage()
    {
        return app()->getLocale();
    }

    public function validateRequest($request, $rules)
    {
        $validator = Validator::make($request->all(), $rules);
        return $validator;
    }
    public function UploadImage($request, $destination_path, $requestFileName)
    {
        if($request->hasFile($requestFileName))
        {
            //$destination_path = 'users/profiles';
            $filename = rand(1,9999999).time() . '.' . $request[$requestFileName]->getClientOriginalExtension();
           // $request[$requestFileName]->move($destination_path, $filename);
            $request[$requestFileName]->storeAs($destination_path, $filename, 's3');
            //return  'https://ayadty.s3.amazonaws.com/' . $destination_path . '/' . $filename;
            return  $destination_path . '/' . $filename;
        }else{
            return null;
        }

    }
    public function UploadMultipleImages($request, $destination_path, $requestFileName)
    {
        if($request->hasFile($requestFileName))
        {
            $data = [];
            
            //$destination_path = 'users/profiles';
            foreach($request[$requestFileName] as $file)
            { 
                
                $filename = time().rand(1111111,9999999).'.'.$file->getClientOriginalExtension();
               
                $file->storeAs($destination_path, $filename, 's3');  
                $data[] = $destination_path . '/' . $filename;  
                $filename= '';
            }

            return json_encode($data);
        }else{
            return null;
        }

    }
    
    /*Payment functions*/
        public function credit($subdomain, $request, $amount, $days, $api_key, $online_card_integration_id, $mobile_wallet_integration_id, $ifram_id, $whoPay) {
        
        
        //step 1:Authentication Request  'paymob'
        $token = $this->getToken($api_key);
        if(!$token)
            return $this->returnErrorResponse(__('general.error_happend'));

        //step 2: Order Registration API
        $order = $this->createOrder($token, $amount);
        
        if($whoPay == 'doctor'){
            // store the operation in our database.
            $this->storeNewPayment($order->id, $amount, $subdomain, $days);   //365 is the number of days the user will buy
        }else{
             $this->storeNewPatientPayment($order->id, $amount, $request);
            
        }
        
        //wallet: 2851905
        //card:2851887
        $integration_id = $online_card_integration_id;  //online card
        if($request->wallet_number)
            $integration_id = $mobile_wallet_integration_id;
        
        
        //step 3: Payment Key Request
        $paymentToken = $this->getPaymentToken($order, $token, $amount, $integration_id);
        if(!$paymentToken){
            return $this->returnErrorResponse(__('general.error_happend'));
        }
        if($request->wallet_number)
        {
         
            $wallet_object  = $this->PayMobileWallet($request->wallet_number,$paymentToken);
            if($wallet_object->pending == true)
            {
                $url = $wallet_object->redirect_url;
            }else{
                return $this->returnErrorResponse($wallet_object->data->message);
            }   
        }else{
             $url = 'https://accept.paymobsolutions.com/api/acceptance/iframes/'.$ifram_id.'?payment_token='.$paymentToken ;
        }
        
        return $this->returnData(__('general.found_success'), 'data', $url);
    }

    
    
    public function payPatient($subdomain, Request $request, $visit)
    {
        // $rules = [
        //     'visit_id'  => 'required'
        // ];
        
        // $validator = $this->validateRequest($request, $rules);
        
        // if($validator->fails())
        //     return $this->returnErrorResponse(__('general.validate_error'), $validator);
            
        
        $visit = Visit::find($visit->id);
        if(!$visit)
            return $this->returnErrorResponse(__('general.found_error'));
        
        $clinic_info = ClinicInfo::first();
        if(!$clinic_info)
            return $this->returnErrorResponse(__('general.found_error'));

        
        if($visit->type == 'diagnosis')
        {
            $amount = $clinic_info->amount * 100;
            
        }else{
            $amount = $clinic_info->consultation_amount * 100;
        }
            
       // $amount = ($visit->amount + $visit->extra_amount) * 100;
        
        
        $conf = DoctorPaymentConf::first();
        if(!$conf)
            return $this->returnErrorResponse(__('general.found_error'));
        
        
        
        $whoPay = 'patient';
        return $this->credit($subdomain, $request, $amount, 0 , $conf->api_key, $conf->integration_online_card_id, $conf->integration_mobile_wallet_id,$conf->iframe_id, $whoPay);

    }
    
    public function callbackPatient(Request $request)
    {
        //call after patient pay for visit.
        
        
        //return response()->json($request->all());
        $conf = DoctorPaymentConf::first();
        if(!$conf)
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
            if(in_array($key, $array)) {
                $connectedString .= $element;
            }
        }
        $secret = $conf->hmac; //env('PAYMOB_HMAC');
        $hased = hash_hmac('sha512', $connectedString, $secret);
        if ( $hased == $hmac) {
            
            if($request->success == "false"){
                
                PatientPyament::where('order_id', $request->order)->update(['status'=> 'error']);
                
                return $this->returnErrorResponse(__('general.paymentError'));
                
            }
            
            PatientPyament::where('order_id', $request->order)->update(['status'=> 'success']);
            $order = PatientPyament::where('order_id', $request->order)->first();
            if(!$order)
                return $this->returnErrorResponse(__('general.found_error'));
    
            //paid status need to be updated.
            $visit = Visit::find($order->visit_id);
            if(!$visit)
                return $this->returnErrorResponse(__('general.found_error'));
        
            //update the visits as intial amount paid.
            $visit->main_amount_paid = 1;
            $visit->save();
        
            $url = 'https://' . $request->route('subdomain') . '.ayadty.com/appointments?PaymentSuccess=S101' ;
            return \Redirect::away($url);

        }
    }
    
        
    private function storeNewPayment($order_id, $amount,$subdomain, $days)
    {
        DB::connection('mysql_kyan')->table('ayadty_payments')->insert([
            'order_id'=> $order_id,
            'amount'=> $amount,
            'subdomain'=> $subdomain,
            'status'=> 'pending',
            'days'=> $days,
            ]);
    }
    private function storeNewPatientPayment($order_id, $amount,$request)
    {
         PatientPyament::create([
            'order_id'=> $order_id,
            'amount'=> $amount,
            'visit_id'=> $request->visit_id,
            'status'=> 'pending',
            'patient_id'=> auth()->user()->id,
            ]);
    }
    
    public function getToken($api_key) {
        $response = Http::post('https://accept.paymob.com/api/auth/tokens', [
           'api_key' =>$api_key
        ]);
        return (isset($response->object()->token) && $response->object()->token ) ? $response->object()->token : null;
    }
    
    public function createOrder($token, $amount) {

        $data = [
            "auth_token" =>   $token,
            "delivery_needed" =>"false",
            "amount_cents"=> $amount,
            "currency"=> "EGP",
            //"items"=> $items,

        ];
        $response = Http::post('https://accept.paymob.com/api/ecommerce/orders', $data);
        return $response->object();
    }

    public function getPaymentToken($order, $token, $amount, $integration_id)
    {
        
        $user = Auth::guard('api')->user();
        $billingData = [
            "apartment" => "803",
            "email" => $user->email,
            "floor" => "42",
            "first_name" => $user->first_name,
            "street" => "Ethan Land",
            "building" => "8028",
            "phone_number" => $user->phone,
            "shipping_method" => "PKG",
            "postal_code" => "01898",
            "city" => "Cairo",
            "country" => "eg",
            "last_name" => $user->last_name,
            "state" => "Utah"
        ];

        $data = [
            "auth_token" => $token,
            "amount_cents" => $amount,
            "expiration" => 3600,
                "order_id" => $order->id,
            "billing_data" => $billingData,
            "currency" => "EGP",
            "integration_id" =>$integration_id,// 2851887 //integration id    //online card 
        ];

        $response = Http::post('https://accept.paymob.com/api/acceptance/payment_keys', $data);
        return (isset($response->object()->token) && $response->object()->token ) ? $response->object()->token : null;
    }
    
    private function PayMobileWallet($wallet_number, $token)
    {
        $data =  [
            "source"    => ["identifier"=> $wallet_number,  "subtype"=> 'WALLET'],
            "payment_token" => $token,
        ];

            
         $response = Http::post('https://accept.paymobsolutions.com/api/acceptance/payments/pay', $data);
         return $response->object();
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

}