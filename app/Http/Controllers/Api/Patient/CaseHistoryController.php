<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Http\Traits\GeneralTrait;
use App\Models\Patient\CaseHistory;
use App\Models\User;
use Illuminate\Http\Request;

class CaseHistoryController extends Controller
{

    use GeneralTrait;
    protected $rules = [
        'patient_id'    => 'required',
        'title'    => 'required',
        'description'    => 'required',
    ];
    public function save(Request $request)
    {
        $validator = $this->validateRequest($request, $this->rules);
        if($validator->fails())
            return $this->returnErrorResponse(__('general.validate_error'), $validator);


        $patient = User::where('id', $request->patient_id)->where('role', 2)->get()->first();
        if(!$patient)
            return $this->returnErrorResponse(__('general.found_error'));

        $data = CaseHistory::create([
            'patient_id'    => $request->patient_id,
            'title'         => $request->title,
            'description'    => $request->description,
        ]);

        return $this->returnSuccessResponse(__('general.add_success'), $data);
    }

    public function update( $subdomain,$id, Request $request)
    {
        $validator = $this->validateRequest($request, $this->rules);
        if($validator->fails())
            return $this->returnErrorResponse(__('general.validate_error'), $validator);


        $history = CaseHistory::find($id);
        if(!$history)
            return $this->returnErrorResponse(__('general.found_error'));

        $patient = User::where('id', $request->patient_id)->where('role', 2)->get()->first();
        if(!$patient)
            return $this->returnErrorResponse(__('general.found_error'));


        $history->patient_id    = $request->patient_id;
        $history->title    = $request->title;
        $history->description    = $request->description;
        $history->save();

        return $this->returnSuccessResponse(__('general.edit_success'), $history);
    }

    
    public function destroy($subdomain, $id)
    {
        $history = CaseHistory::find($id);
        if(!$history)
            return $this->returnErrorResponse(__('general.found_error'));


        $history->delete();

        return $this->returnSuccessResponse(__('general.delete_success'));
    }

    public function getByPatient($subdomain, $patient_id)
    {
        $patient = User::where('id', $patient_id)->where('role', 2)->get()->first();
        if(!$patient)
            return $this->returnErrorResponse(__('general.found_error'));

        $history = CaseHistory::where('patient_id', $patient_id)->paginate(10);

        return $this->returnData(__('general.found_success'), 'data', $history);

    }
}
