<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Traits\GeneralTrait;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductQnty;
use Illuminate\Http\Request;

class ProductQntyController extends Controller
{
    use GeneralTrait;
    public function save(Request $request)
    {
        $rules = [
            'product_id'    => 'required',
            'qnty'          => 'required',
            'cost'          => 'required',
        ];
        $validator = $this->validateRequest($request, $rules);
        if($validator->fails())
            return $this->returnErrorResponse(__('general.validate_error'), $validator);

        $product = Product::find($request->product_id);
        if(!$product)
            return $this->returnErrorResponse(__('general.found_error'));

        //update product quantity
        $product->qnty +=$request->qnty;
        $product->save();

        $data = ProductQnty::create([
            'product_id'    => $product->id,
            'qnty'          => $request->qnty,
            'cost'          => $request->cost,
            'supplier_name'          => $request->supplier_name,

        ]);

        //add the product info to response
       $product= $data->product;
          $product['category']=$data->product->category->name;
        return $this->returnSuccessResponse(__('general.add_success'), $data);
    }


    //subdomain
    public function update( $id, Request $request)
    {
        $rules = [
            'product_id'    => 'required',
            'qnty'          => 'required',
            'cost'          => 'required',
        ];
        $validator = $this->validateRequest($request, $rules);
        if($validator->fails())
            return $this->returnErrorResponse(__('general.validate_error'), $validator);

        $data = ProductQnty::find($id);
        if(!$data)
            return $this->returnErrorResponse(__('general.found_error'));

        $product = Product::find($data->product_id);
        if(!$product)
            return $this->returnErrorResponse(__('general.found_error'));

        //deduct the previous amount of quantity
        $product->qnty -= $data->qnty;


        //quantity update to be for another product
        if($request->product_id != $data->product_id)
        {
            $product->save();
            $product = Product::find($request->product_id);
            if(!$product)
                return $this->returnErrorResponse(__('general.found_error'));
        }
        //add the new quantity
        $product->qnty += $request->qnty;

        //save the product
        $product->save();

        $data->product_id = $request->product_id;
        $data->qnty = $request->qnty;
        $data->cost = $request->cost;
        $data->save();

        //to add the product info in the repsonse.
        $data->product;


        return $this->returnSuccessResponse(__('general.edit_success'), $data);
    }

    //subdomain
    public function destroy( $id)
    {
        $data = ProductQnty::find($id);
        if(!$data)
            return $this->returnErrorResponse(__('general.found_error'));


        $product = Product::find($data->product_id);
        if(!$product)
            return $this->returnErrorResponse(__('general.found_error'));


        $product->qnty -= $data->qnty;
        $product->save();


        $data->delete();
        return $this->returnSuccessResponse(__('general.delete_success'));

    }
}
