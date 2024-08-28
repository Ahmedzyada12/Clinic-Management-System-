<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\GeneralTrait;
use App\Models\DoctorPaymentConf;
use Illuminate\Http\Request;

class DoctorPaymentController extends Controller
{
    use GeneralTrait;
    public function update($subdomain, Request $request)
    {
        
        $rules = [
            'iframe_id' => 'required',
            'integration_online_card_id' => 'required',
            'integration_mobile_wallet_id' => 'required',
            'api_key' => 'required',
            'hmac' => 'required',
        ];

    
        $validator = $this->validateRequest($request, $rules);
        if($validator->fails())
            return $this->returnErrorResponse(__('general.validate_error'), $validator);


        $data = DoctorPaymentConf::first();
        if(!$data)
            return $this->returnErrorResponse(__('general.found_error'));

        $data->iframe_id = $request->iframe_id;
        $data->integration_online_card_id = $request->integration_online_card_id;
        $data->integration_mobile_wallet_id = $request->integration_mobile_wallet_id;
        $data->api_key = $request->api_key;
        $data->hmac = $request->hmac;
        $data->save();


        return $this->returnSuccessResponse(__('general.edit_success'), $data);
    }

        public function getData()
        {
            $data = DoctorPaymentConf::first();
            if(!$data)
                return $this->returnErrorResponse(__('general.found_error'));
                
            return $this->returnData(__('general.found_success'), 'data', $data);
        }

}
