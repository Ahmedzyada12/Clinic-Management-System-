<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\GeneralTrait;
use App\Models\Appointment;
use App\Models\ClinicInfo;
use App\Models\DoctorPaymentConf;
use App\Models\patientInfo;
use App\Models\Visit;
use App\Models\User;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\Patient\PatientPyament;

class BillingController extends Controller
{
    use GeneralTrait;
    
    public function billingInfo($subdomain)
    {
        $lang = $this->returnLocaleLanguage();
        $objResult = new \stdClass();
        
        // getting plans from saas application
        $plans = DB::connection('mysql_saas')->table('plans')->get([
            'name_'.$lang . ' as name',
            'text_'.$lang . ' as text',
            'days',
            'price',
            'best',
            'most'
        ])->map(function($item)use ($lang){
            if($lang == 'ar')
            {
                $item->description = explode('،',$item->text);
            }else{
                $item->description = explode(',',$item->text);
            }
            unset($item->text);
            return $item;
        });
         
        $objResult->plans = $plans;
        
        $item = DB::connection('mysql_kyan')->table('domains')->where('subdomain', $subdomain)->get([
            'expiry_date', 'days as plan','promo_code','expired_promo_codes'
            ])->first();
        $expired = 0;
        if($item->promo_code){
            
            //this function check if the user have a promocode, is it expired or not 
            //expired promocodes stored in the form of comma seperated string in expired_promo_codes column in database
            //if the promocode field in expired_promo_codes field it is expired.
            if($item->expired_promo_codes )
            {
                $expired_tokens = explode( ',', $item->expired_promo_codes );
                foreach($expired_tokens as $promo)
                {
                    if($item->promo_code == trim($promo)){
                        $expired = 1;
                        break;
                    }
                }
            }
        }
        
        //get the plan object
        $user_plan = DB::connection('mysql_saas')->table('plans')->where('days', $item->plan)->get([
            'name_'.$lang . ' as name',
            'text_'.$lang . ' as text',
            'days',
            'price',
            'best',
            'most'
        ])->first();
        
        if($user_plan)
        {
            if($lang == 'ar')
            {
                $user_plan->description = explode('،',$user_plan->text);
            }else{
                $user_plan->description = explode(',',$user_plan->text);
            }
            unset($user_plan->text);
        }

        $item->planData = $user_plan;
        // End getting the plan object (JUST to view it with its name).
        
        $item->token_expired = $expired;

        $objResult->user_data = $item;
        
        return $this->returnData(__('general.found_success'), 'data', $objResult);
    }
    
    
    /*
        This method check wheather the client have a promocode or not (promocode added when register for the first time or from admin portal)
        Then: checks wheather this promo code exists in seller table or not.
        if it exists it will add the promo code to expired_promo_codes (comma seperated strings), so that if client will use it again it will be expire,
        then the method apply the offer of 2000 L.E for one Year.
        
        If the use doesn't have any promo code the plan will depend only on the `plan` which is the days of the plan, 
        getting the object from database to make sure this plan exists, then apply the new plan to client.
    */
    
    public function pay($subdomain, Request $request)
    {
       // DB::connection('mysql_kyan')->table('domains')->where('subdomain', $subdomain)->get();
    
        $lang = $this->returnLocaleLanguage();
        $existingUser = DB::connection('mysql_kyan')->table('domains')->where('subdomain', $subdomain)->get()->first();
        if(!$existingUser)
            return $this->returnErrorResponse(__('general.found_error'));

        if($existingUser->promo_code && $existingUser->promo_code != '')
        {
            
            $promo_record = DB::connection('mysql_promocode')->table('users')->where('promocode', $existingUser->promo_code)->get()->first();
            if(!$promo_record)
                return $this->returnErrorResponse(__('general.promocodeNotFound'));
                
                
            
            /*if the user use this promo code before*/
            $arr = explode(',',$existingUser->expired_promo_codes);
            foreach($arr as $r)
            {
                if(trim($r) == $existingUser->promo_code)
                {
                    return $this->returnErrorResponse(__('general.promo_expired'));
                }
            }
            
            // $arr [] = $existingUser->promo_code;
            // DB::connection('mysql_kyan')->table('domains')->where('subdomain', $subdomain)->update(['expired_promo_codes'=> implode(", ",  $arr)]);
            
            $amount = $promo_record->offer *100;   //offer
            $days = 365;        //1 yera
            //DB::connection('mysql_kyan')->table('domains')->where('subdomain', $subdomain)->update([]);
            
        }else{
            

            $user_plan = DB::connection('mysql_saas')->table('plans')->where('days', $request->plan)->get([
                'name_'.$lang . ' as name',
                'text_'.$lang . ' as text',
                'days',
                'price',
                'best',
                'most'
            ])->first();
            
            if(!$user_plan)
                return $this->returnErrorResponse(__('general.found_error'));
                
            $days = $user_plan->days;
            $amount = $user_plan->price * 3000; //dollar in egp
        }
        
        
        //$api_key: api_key
        //PAYMOB_INTEGRATION_ONLINE_CARD_ID: online card
        //PAYMOB_INTEGRATION_MOBILE_WALLET_ID: mobile wallet
        //$whoPay: who will pay.
        /*
        *   this parameter inditcate who will pay if it have a value 'doctor' that means doctor will renew his package.
            if the value is 'patient' that means patient will pay to doctor.
        */
        
        $whoPay = 'doctor';
        return $this->credit($subdomain, $request, $amount, $days, env('PAYMOB_API_KEY'), env('PAYMOB_INTEGRATION_ONLINE_CARD_ID'), env('PAYMOB_INTEGRATION_MOBILE_WALLET_ID'),env('PAYMOB_IFRAME_ID'), $whoPay);
    }
    
    #######################################
    #       PAYMOB Controller             #
    #######################################



    public function callback(Request $request)
    {
  
  //return response()->json($request->all());
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
        $secret = env('PAYMOB_HMAC');
        $hased = hash_hmac('sha512', $connectedString, $secret);
        if ( $hased == $hmac) {
            
            if($request->success == "false")
                return $this->returnErrorResponse(__('general.paymentError'));
            //get the order id for this payment
            DB::connection('mysql_kyan')->table('ayadty_payments')->where('order_id', $request->order)->update(['status'=> 'success']);
            $order = DB::connection('mysql_kyan')->table('ayadty_payments')->where('order_id', $request->order)->get()->first();
            if(!$order)
                return $this->returnErrorResponse(__('general.found_error'));
                
            //get the subdomain owner for this payment
            $userObject = DB::connection('mysql_kyan')->table('domains')->where('subdomain', $order->subdomain)->get()->first();
            if(!$userObject)
                return $this->returnErrorResponse(__('general.found_error'));
            
            //update the info for this subdomain with days based on this payment.
            $current_expiry_date = $userObject->expiry_date;
            $expiry_date = date('Y-m-d H:i:s', strtotime($current_expiry_date . ' + '.$order->days .' days'));
            
            $arr = ['expiry_date'   => $expiry_date, 'paid_date'    => date('Y-m-d'), 'days'=> $order->days,'status'=> 'paid', 'account_status' => 'running'];
            
            //if the user used promocode: add the seller commession and move the code to expired promocodes.
            if($userObject->promo_code){

                //append the commession to the seller
                $seller_result = $this->addSellerCommession($userObject->promo_code);

                $arr_expired  = explode(',',$userObject->expired_promo_codes);
                $arr_expired [] = $userObject->promo_code;
               // DB::connection('mysql_kyan')->table('domains')->where('subdomain', $subdomain)->update(['expired_promo_codes'=> implode(", ",  $arr_expired)]);
                
                $arr['expired_promo_codes'] = implode(", ",  $arr_expired);
                $arr['promo_code']  = NULL;
            }
            
            

            DB::connection('mysql_kyan')->table('domains')->where('subdomain', $order->subdomain)->update($arr);
            $data = DB::connection('mysql_kyan')->table('domains')->where('subdomain', $order->subdomain)->get()->first();
            
            //return $this->returnSuccessResponse(__('general.paymentSuccess'), $data);
            
            $url = 'https://' . $order->subdomain . '.ayadty.com?PaymentSuccess=S101' ;
            return \Redirect::away($url);
            
        }

        return $this->returnErrorResponse(__('general.payment_not_secure'));
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
    /**
     * NOTE: if the client want to use the promo code this method must be used before the client Pay.
     */
    public function addPromocode($subdomain, Request $request)
    {
        $rules = [
            'promocode' => 'required'
            ];    
            
        $validator = $this->validateRequest($request, $rules);
        if($validator->fails())
            return $this->returnErrorResponse(__('general.validate_error'), $validator);
            
            
        $promo_code = DB::connection('mysql_promocode')->table('users')->where('promocode', $request->promocode)->get()->first();
        if(!$promo_code)
            return $this->returnErrorResponse(__('general.promocodeNotFound'));
            
        $client = DB::connection('mysql_kyan')->table('domains')->where('subdomain', $subdomain)->get()->first();
        if(!$client)
            return $this->returnErrorResponse(__('general.found_error'));
            
        
        $expired = 0;   //flag  1 if the promo code is expired.

        //get the tokens used before by this client.
        $expired_tokens = explode( ',', $client->expired_promo_codes );
        foreach($expired_tokens as $promo)
        {
            if($request->promocode == trim($promo)){
                $expired = 1;
                break;
            }
        }
        
        if($expired == 0)
        {
            //store the promo code for the user to be used.
            //in pay method.
            DB::connection('mysql_kyan')->table('domains')->where('subdomain', $subdomain)->update(['promo_code'  => $request->promocode]);
        }else{
            return $this->returnErrorResponse(__('general.promo_expired'));
        }

        return $this->returnSuccessResponse(__('general.add_promo_success'), $promo_code->offer);
    }

    //this method adds the seller's commession to the seller 
    private function addSellerCommession($promo_code)
    {
            $seller = DB::connection('mysql_promocode')
                        ->table('users')
                        ->where('promocode', $promo_code)
                        ->first();
            if(!$seller)
                return false;

            $earned = ($seller->commession / 100) * $seller->offer;
            $query = DB::connection('mysql_promocode')
                        ->table('users')
                        ->where('promocode', $promo_code);
                        
            $query->increment('balance', $earned);
            $query->increment('history_balance', $earned);

            return true;

    }
    
    // public function payPatient($subdomain, Request $request)
    // {
    //     $rules = [
    //         'visit_id'  => 'required'
    //     ];
        
    //     $validator = $this->validateRequest($request, $rules);
        
    //     if($validator->fails())
    //         return $this->returnErrorResponse(__('general.validate_error'), $validator);
            
        
    //     $visit = Visit::find($request->visit_id);
    //     if(!$visit)
    //         return $this->returnErrorResponse(__('general.found_error'));
        
    //     $clinic_info = ClinicInfo::first();
    //     if(!$clinic_info)
    //         return $this->returnErrorResponse(__('general.found_error'));

        
    //     if($visit->type == 'diagnosis')
    //     {
    //         $amount = $clinic_info->amount * 100;
            
    //     }else{
    //         $amount = $clinic_info->consultation_amount * 100;
    //     }
            
    //   // $amount = ($visit->amount + $visit->extra_amount) * 100;
        
        
    //     $conf = DoctorPaymentConf::first();
    //     if(!$conf)
    //         return $this->returnErrorResponse(__('general.found_error'));
        
        
        
    //     $whoPay = 'patient';
    //     return $this->credit($subdomain, $request, $amount, 0 , $conf->api_key, $conf->integration_online_card_id, $conf->integration_mobile_wallet_id,$conf->iframe_id, $whoPay);

    // }
    
    // public function callbackPatient(Request $request)
    // {
    //     //call after patient pay for visit.
        
        
    //     //return response()->json($request->all());
    //     $conf = DoctorPaymentConf::first();
    //     if(!$conf)
    //         return $this->returnErrorResponse(__('general.found_error'));

    //     $data = $request->all();
    //     ksort($data);
    //     $hmac = $data['hmac'];
    //     $array = [
    //         'amount_cents',
    //         'created_at',
    //         'currency',
    //         'error_occured',
    //         'has_parent_transaction',
    //         'id',
    //         'integration_id',
    //         'is_3d_secure',
    //         'is_auth',
    //         'is_capture',
    //         'is_refunded',
    //         'is_standalone_payment',
    //         'is_voided',
    //         'order',
    //         'owner',
    //         'pending',
    //         'source_data_pan',
    //         'source_data_sub_type',
    //         'source_data_type',
    //         'success',
    //     ];
    //     $connectedString = '';
    //     foreach ($data as $key => $element) {
    //         if(in_array($key, $array)) {
    //             $connectedString .= $element;
    //         }
    //     }
    //     $secret = $conf->hmac; //env('PAYMOB_HMAC');
    //     $hased = hash_hmac('sha512', $connectedString, $secret);
    //     if ( $hased == $hmac) {
            
    //         if($request->success == "false"){
                
    //             PatientPyament::where('order_id', $request->order)->update(['status'=> 'error']);
    //             return $this->returnErrorResponse(__('general.paymentError'));
                
    //         }
            
    //         PatientPyament::where('order_id', $request->order)->update(['status'=> 'success']);
    //         $order = PatientPyament::where('order_id', $request->order)->first();
    //         if(!$order)
    //             return $this->returnErrorResponse(__('general.found_error'));
    
    //         //paid status need to be updated.
    //         $visit = Visit::find($order->visit_id);
    //         if(!$visit)
    //             return $this->returnErrorResponse(__('general.found_error'));
        
    //         //update the visits as intial amount paid.
    //         $visit->main_amount_paid = 1;
    //         $visit->save();
        
    //         $url = 'https://' . $request->route('subdomain') . '.ayadty.com/appointments?PaymentSuccess=S101' ;
    //         return \Redirect::away($url);

    //     }
    // }

}
