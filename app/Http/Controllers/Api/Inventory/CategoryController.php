<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Traits\GeneralTrait;
use App\Models\Inventory\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use GeneralTrait;
    public function list()
    {
        $lang = $this->returnLocaleLanguage();
        $data = tap(Category::with('products')->select('id','name_'.$lang .' as name', 'name_ar', 'name_en')->paginate(10))->map(function($item){
            $item->products_number = count($item->products);
            unset($item->products);
            return $item;
        });


        return $this->returnData(__('general.found_success'), 'data', $data);
    }

    public function save(Request $request)
    {
        $rules = [
            'name_ar'   => 'required',
            'name_en'   => 'required',
        ];

        $validator = $this->validateRequest($request, $rules);
        if($validator->fails())
            return $this->returnErrorResponse(__('general.validate_error'), $validator);

        $data = Category::create([
            'name_ar'   => $request->name_ar,
            'name_en'   => $request->name_en,
        ]);

        //return the category based on the language.
        $data = $this->modifyCategoryResponseFormat($data);

        return $this->returnSuccessResponse(__('general.add_success'), $data);
    }

    public function update($subdomain, $id, Request $request)
    {
        $rules = [
            'name_ar'   => 'required',
            'name_en'   => 'required',
        ];

        $validator = $this->validateRequest($request, $rules);
        if($validator->fails())
            return $this->returnErrorResponse(__('general.validate_error'), $validator);
        
        $data = Category::find($id);
        if(!$data)
            return $this->returnErrorResponse(__('general.found_error'));

        $data->name_ar = $request->name_ar;
        $data->name_en = $request->name_en;
        $data->save();

        $data = $this->modifyCategoryResponseFormat($data);


        return $this->returnSuccessResponse(__('general.edit_success'), $data);
    }


    public function destroy($subdomain, $id)
    {
        $data = Category::find($id);
        if(!$data)
            return $this->returnErrorResponse(__('general.found_error'));

        $data->delete();

        return $this->returnSuccessResponse(__('general.delete_success'));
    }

    public function search( $subdomain, Request $request)
    {
        $lang = $this->returnLocaleLanguage();
        $keyword = $request->keyword;
        $data = Category::select('id', 'name_' . $lang . ' as name')
                        ->where('name_ar', 'LIKE', '%'. $keyword . '%')
                        ->orWhere('name_en', 'LIKE', '%'. $keyword . '%')
                        ->paginate(10);

        return $this->returnData(__('general.found_success'), 'data', $data);
    }

    public function multiDelete(Request $request)
    {
        $rules = [
            'ids'   => 'required'
            ];
        $validator = $this->validateRequest($request, $rules);
        if($validator->fails())
            return $this->returnErrorResponse(__('general.validate_error'), $validator);
            
        $arr_ids = explode(',', $request->ids);
        Category::whereIn('id', $arr_ids)->delete();
        
        return $this->returnSuccessResponse(__('general.delete_success'));
    }

    //return only one format based on the language
    private function modifyCategoryResponseFormat($category)
    {
        $lang = $this->returnLocaleLanguage();
        $category->name = ($lang == 'ar') ? $category->name_ar : $category->name_en;
        // unset($category->name_ar);
        // unset($category->name_en);
        return $category;
    }
}
