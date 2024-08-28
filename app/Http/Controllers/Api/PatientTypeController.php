<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PatientType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Traits\GeneralTrait;

class PatientTypeController extends Controller
{
    
    use GeneralTrait;
    public function list()
    {
        $lang = $this->returnLocaleLanguage();
        $data = PatientType::select('id', 'name_' . $lang . ' as name')->paginate(10);
        return $this->returnData(__('general.found_success'), 'data', $data);    
    }
    
    public function getAll()
    {
        $lang = $this->returnLocaleLanguage();
        $data = PatientType::select('id','name_' . $lang . ' as name')->get();
        return $this->returnData(__('general.found_success'), 'data', $data);    
    }
    
    public function save(Request $request)
    {
        $rules = [
            'name_ar'       => 'required|max:255',
            'name_en'       => 'required|max:255',
        ];
        $validator = $this->validateRequest($request, $rules);
        if($validator->fails())
            return $this->returnErrorResponse(__('general.validate_error'), $validator);

        $type = PatientType::create([
            'name_ar'    => $request->name_ar, 
            'name_en'     => $request->name_en, 
        ]);

        return $this->returnSuccessResponse(__('general.add_success'), $type);

    }
    
    public function update($subdomain,$id, Request $request)
    {
         $lang = $this->returnLocaleLanguage();
         
        $rules = [
            'name_ar'    => 'required|max:255',
            'name_en'     => 'required|max:255',
        ];
        $validator = $this->validateRequest($request, $rules);
        if($validator->fails())
            return $this->returnErrorResponse(__('general.validate_error'), $validator);

        $patientType = PatientType::where('id', $id)->first();
        if(!$patientType)
            return $this->returnErrorResponse(__('general.found_error'));

        $patientType->name_ar    = $request->name_ar;
        $patientType->name_en     = $request->name_en;

        $patientType->save();
        $patientType->name  = ($lang == 'ar') ? $patientType->name_ar : $patientType->name_en;

        return $this->returnSuccessResponse(__('general.edit_success'), $patientType);
    }
    
    public function destroy($subdomain,$id)
    {
        $type = PatientType::find($id);
        if(!$type)
            return $this->returnErrorResponse(__('general.found_error'));

        $type->delete();

        return $this->returnSuccessResponse(__('general.delete_success'));
    }

    public function search(Request $request)
    {
        $lang = $this->returnLocaleLanguage();
        $keyword = $request->keyword;
        $users = PatientType::select('id', 'name_' . $lang . ' as name')->where('name_ar','LIKE', '%'. $keyword . '%')
        ->orWhere('name_en','LIKE', '%'. $keyword . '%')
        ->paginate(10);
            
        return $this->returnData(__('general.found_success'), 'data', $users);
    }

}
