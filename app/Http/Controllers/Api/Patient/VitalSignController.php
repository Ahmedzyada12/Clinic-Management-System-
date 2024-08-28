<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Http\Traits\GeneralTrait;
use App\Models\Patient\VitalSign;
use App\Models\User;
use Illuminate\Http\Request;

class VitalSignController extends Controller
{
    use GeneralTrait;
    protected  $rules = [
        'patient_id'    => 'required|numeric',
        'heart_rate'    => 'required|numeric',
        'systolic_blood_pressure'   => 'required|numeric',
        'diastolic_blood_pressure'  => 'required|numeric',
        'temperature'   => 'required|numeric',
        'oxygen_saturation' => 'required|numeric',
        'respiratory_rate'  => 'required|numeric',
        'bmi_weight'    => 'required|numeric',
        'bmi_height'    => 'required|numeric'
    ];

    public function getByPatient($subdomain, $patient_id)
    {
        $patient = User::where('id', $patient_id)->where('role', 2)->get()->first();
        if(!$patient)
            return $this->returnErrorResponse(__('general.found_error'));

        $data = VitalSign::where('patient_id', $patient_id)->paginate(10);
        return $this->returnData(__('general.found_success'), 'data', $data);

    }

    public function save(Request $request)
    {   

        $validator = $this->validateRequest($request, $this->rules);
        if($validator->fails())
            return $this->returnErrorResponse(__('general.validate_error'), $validator);

        $patient = User::where('id', $request->patient_id)->where('role', 2)->get()->first();
        if(!$patient)
            return $this->returnErrorResponse(__('general.found_error'));


        $data = VitalSign::create([
            'patient_id'    => $request->patient_id,
            'heart_rate'    => $request->heart_rate,
            'systolic_blood_pressure'   => $request->systolic_blood_pressure,
            'diastolic_blood_pressure'  => $request->diastolic_blood_pressure,
            'temperature'   => $request->temperature,
            'oxygen_saturation' => $request->oxygen_saturation,
            'respiratory_rate'  => $request->respiratory_rate,
            'bmi_weight'    => $request->bmi_weight,
            'bmi_height'    => $request->bmi_height
        ]);

        return $this->returnSuccessResponse(__('general.add_success'), $data);        
    }

    public function update($subdomain, $id, Request $request)
    {
        $validator = $this->validateRequest($request, $this->rules);
        if($validator->fails())
            return $this->returnErrorResponse(__('general.validate_error'), $validator);


        $patient = User::where('id', $request->patient_id)->where('role', 2)->get()->first();
        if(!$patient)
            return $this->returnErrorResponse(__('general.found_error'));

        $vitalSign = VitalSign::find($id);
        if(!$vitalSign)
            return $this->returnErrorResponse(__('general.found_error'));
        
            $vitalSign->patient_id    = $request->patient_id;
            $vitalSign->heart_rate    = $request->heart_rate;
            $vitalSign->systolic_blood_pressure   = $request->systolic_blood_pressure;
            $vitalSign->diastolic_blood_pressure  = $request->diastolic_blood_pressure;
            $vitalSign->temperature   = $request->temperature;
            $vitalSign->oxygen_saturation = $request->oxygen_saturation;
            $vitalSign->respiratory_rate  = $request->respiratory_rate;
            $vitalSign->bmi_weight    = $request->bmi_weight;
            $vitalSign->bmi_height    = $request->bmi_height;
            $vitalSign->save();

            return $this->returnSuccessResponse(__('general.edit_success'),$vitalSign);
    }

    public function destroy($subdomain, $id)
    {
        $vitalSign = VitalSign::find($id);
        if(!$vitalSign)
            return $this->returnErrorResponse(__('general.found_error'));

        $vitalSign->delete();

        return $this->returnSuccessResponse(__('general.delete_success'));


    }
}
