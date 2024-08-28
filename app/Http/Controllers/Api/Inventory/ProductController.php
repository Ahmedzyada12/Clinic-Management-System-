<?php

namespace App\Http\Controllers\Api\inventory;

use App\Http\Controllers\Controller;
use App\Http\Traits\GeneralTrait;
use App\Models\Inventory\Category;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductQnty;
use App\Models\Visit;
use App\Models\VisitProduct;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use GeneralTrait;
    public function list()
    {
        $lang = $this->returnLocaleLanguage();
        $data = Product::with('category:id,name_' . $lang . ' as name')->paginate(10);
        return $this->returnData(__('general.found_success'), 'data', $data);
    }

    public function listServices()
    {
        $lang = $this->returnLocaleLanguage();
        $data = Product::where('type', 'service')->with('category:id,name_' . $lang . ' as name')->paginate(10);
        return $this->returnData(__('general.found_success'), 'data', $data);
    }

    public function save(Request $request)
    {
        $rules = [
            'name'  => 'required',
            'type'  => 'required',
            'price' => 'required|numeric',
            'desc'   => 'required',
            'cat_id'   => 'required',
        ];

        $validator = $this->validateRequest($request, $rules);
        if ($validator->fails())
            return $this->returnErrorResponse(__('general.validate_error'), $validator);

        //check the existance of category for that product.
        $cat = Category::find($request->cat_id);
        if (!$cat)
            return $this->returnErrorResponse(__('general.found_error'));


        //products in specific service
        //$products_info = $request->product_info_qty;


        $data = Product::create([
            'name'  => $request->name,
            'type'  => $request->type,
            'price' => $request->price,
            'description'   => $request->desc,
            'qnty'  => 0,
            'alert_qty'  => ($request->alert_qty) ? $request->alert_qty : 10,
            'category_id'   => $request->cat_id,
        ]);

        $lang = $this->returnLocaleLanguage();
        if ($data->category) {
            $data->category->name = ($lang == 'ar') ? $data->category->name_ar : $data->category->name_en;
        }

        return $this->returnSuccessResponse(__('general.add_success'), $data);
    }
    public function saveService(Request $request)
    {

        $rules = [
            'name'  => 'required',
            'type'  => 'required',
            'price' => 'required|numeric',
            'desc'   => 'required',
            'cat_id'   => 'required',
        ];

        $validator = $this->validateRequest($request, $rules);
        if ($validator->fails())
            return $this->returnErrorResponse(__('general.validate_error'), $validator);

        //check the existance of category for that product.
        $cat = Category::find($request->cat_id);
        if (!$cat)
            return $this->returnErrorResponse(__('general.found_error'));


        //products in specific service
        //$products_info = $request->product_info_qty;


        $data = Product::create([
            'name'  => $request->name,
            'type'  => 'service',
            'price' => $request->price,
            'description'   => $request->desc,
            'qnty'  => 0,
            'alert_qty'  => ($request->alert_qty) ? $request->alert_qty : 10,
            'category_id'   => $request->cat_id,
            'products_info_qty'   => json_encode($request->products),
        ]);

        $lang = $this->returnLocaleLanguage();
        if ($data->category) {
            $data->category->name = ($lang == 'ar') ? $data->category->name_ar : $data->category->name_en;
        }

        return $this->returnSuccessResponse(__('general.add_success'), $data);
    }
    public function update($subdomain, $id, Request $request)
    {
        $rules = [
            'name'      => 'required',
            'type'      => 'required',
            'price'     => 'required|numeric',
            'desc'      => 'required',
            'cat_id'    => 'required',
        ];

        $validator = $this->validateRequest($request, $rules);
        if ($validator->fails())
            return $this->returnErrorResponse(__('general.validate_error'), $validator);

        $cat = Category::find($request->cat_id);
        if (!$cat)
            return $this->returnErrorResponse(__('general.found_error'));

        //check the existance of product.
        $product = Product::find($id);
        if (!$product)
            return $this->returnErrorResponse(__('general.found_error'));

        $product->name          = $request->name;
        $product->type          = $request->type;
        $product->price         = $request->price;
        $product->description   = $request->desc;
        $product->category_id   = $request->cat_id;

        if ($request->alert_qty)
            $product->alert_qty = $request->alert_qty;

        $lang = $this->returnLocaleLanguage();
        if ($product->category) {
            $product->category->name = ($lang == 'ar') ? $product->category->name_ar : $product->category->name_en;
        }
        $product->save();


        return $this->returnSuccessResponse(__('general.edit_success'), $product);
    }



    public function updateService($subdomain, $id, Request $request)
    {
        $rules = [
            'name'      => 'required',
            'type'      => 'required',
            'price'     => 'required|numeric',
            'desc'      => 'required',
            'cat_id'    => 'required',
        ];

        $validator = $this->validateRequest($request, $rules);
        if ($validator->fails())
            return $this->returnErrorResponse(__('general.validate_error'), $validator);

        $cat = Category::find($request->cat_id);
        if (!$cat)
            return $this->returnErrorResponse(__('general.found_error'));

        //check the existance of product.
        $product = Product::find($id);
        if (!$product)
            return $this->returnErrorResponse(__('general.found_error'));

        $product->name          = $request->name;
        $product->type          = $request->type;
        $product->price         = $request->price;
        $product->description   = $request->desc;
        $product->category_id   = $request->cat_id;
        $product->products_info_qty   = json_encode($request->products);

        if ($request->alert_qty)
            $product->alert_qty = $request->alert_qty;

        $lang = $this->returnLocaleLanguage();
        if ($product->category) {
            $product->category->name = ($lang == 'ar') ? $product->category->name_ar : $product->category->name_en;
        }
        $product->save();


        return $this->returnSuccessResponse(__('general.edit_success'), $product);
    }

    public function destroy($subdomain, $id)
    {
        //check the existance of product.
        $product = Product::find($id);
        if (!$product)
            return $this->returnErrorResponse(__('general.found_error'));


        $product->delete();

        return $this->returnSuccessResponse(__('general.delete_success'));
    }

    public function search($subdomain, Request $request)
    {
        $keyword = $request->keyword;
        $products = Product::where('name', 'LIKE', '%' . $keyword . '%')->paginate(10);

        return $this->returnData(__('general.found_success'), 'data', $products);
    }
    public function listByCategory(Request $request)
    {
        $validator = $this->validateRequest($request, ['category_id'    => 'required']);
        if ($validator->fails())
            return $this->returnErrorResponse(__('general.validate_error'), $validator);

        $lang = $this->returnLocaleLanguage();

        $id = $request->category_id;
        $data = Product::with('category:id,name_' . $lang . ' as name')->where('category_id', $id)->where('type', '!=', 'service')->get();
        return $this->returnData(__('general.found_success'), 'data', $data);
    }

    public function itemCard($subdomain,$id)
    {
        //check product by Id
        $product = Product::find($id);

        if (!$product) {
            return $this->returnErrorResponse(__('general.found_error'));
        }
        $visitIds = VisitProduct::where('product_id', $product->id)->pluck('visit_id');           //get visits from table visits_products by productId  to get some data by these visits

        $suppliers = ProductQnty::where('product_id', $product->id)->distinct()->pluck('supplier_name')->toArray();


        $doctors = Visit::whereIn('id', $visitIds)->with('doctor')->get()                        //get doctors from visit
            ->pluck('doctor.first_name')
            ->unique()
            ->toarray();

        $quantityUsed = VisitProduct::where('product_id', $product->id)->sum('quantity');           //get quantityUsed from visit_products
        $productRevenus =   $quantityUsed * $product->price;                                       //calculate the total revenues for this product
        $AmountForOroduct = ProductQnty::where('product_id', $product->id)                        //calculate total amount  for ptoduct
            ->pluck('qnty')
            ->map(function ($quantity) use ($product) {
                return ($quantity * $product->price);
            })
            ->sum();

        $totalCostForProduct = ProductQnty::where('product_id', $product->id)->sum('cost');

        $totalPaidAmountForProduct = $AmountForOroduct + $totalCostForProduct;                      //total paid amount for the product
        return response()->json([
            'suppliers' => $suppliers,
            'doctors' => $doctors,
            'quantity_used' => $quantityUsed,
            'product_revenus' => $productRevenus,
            'total_paid_amount' => $totalPaidAmountForProduct,

        ]);
    }
    
        public function detailsOfProductProcess($subdomain,$id)
    {

        //check product by Id
        $product = Product::find($id);

        if (!$product) {
            return $this->returnErrorResponse(__('general.found_error'));
        }
        $expenseProduct = VisitProduct::with(['visit', 'visit.doctor'])
        ->where('product_id', $product->id)
        ->paginate(10);
        $expenseProducts = $expenseProduct->map(function ($item) use ($product){
            return [
                'doctor_name' => $item->visit->doctor->first_name . " " . $item->visit->doctor->last_name,
                'quantity' => $item->quantity,
                'price' => $product->price,
                'updated_at' => date('Y-m-d', strtotime($item->updated_at)),
                'type'=>'سحب',
            ];
        });

            $depositProduct=ProductQnty::where('product_id',$product->id)->paginate(10);
               $depositProducts = $depositProduct->map(function ($item) use ($product) {
            return [
                'supplier_name' => $item->supplier_name,
                'quantity' => $item->qnty,
                'price' => $product->price,
                'type'=>'ايداع',
                'updated_at' => date('Y-m-d', strtotime($item->updated_at)),
            ];
        });

        return response()->json([
            'product_name'=>$product->name,
            'expense'=>$expenseProducts,
            'deposit'=>$depositProducts,

        ]);

    }

}
