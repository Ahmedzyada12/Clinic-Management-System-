<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\GeneralTrait;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\ProductQuantityResource;
use App\Http\Resources\TotalPaidResource;
use Carbon\Carbon;
use App\Models\Inventory\ProductQnty;

class ExpenseController extends Controller
{

    use GeneralTrait;
    public function store(Request $request)
    {
        $rules = [
            'description' => 'required|string',
            'price' => 'required|numeric',
            'quantity' => 'nullable|integer',

        ];
        $validation = Validator::make($request->all(), $rules);
        if ($validation->fails()) {
            return  $errors = $validation->errors();
        }
        $quantity = $request->quantity ?? 1;
        $expense = Expense::create([
            'description' => $request->description,
            'price' => $request->price,
            'quantity' => $quantity,

        ]);

        return $this->returnSuccessResponse(__('general.add_success'), $expense);
    }

    public function delete($subdomain,$id)
    {

        //check expense found in db
        $expense = Expense::find($id);
        if (!$expense)
            return $this->returnErrorResponse(__('general.found_error'));

        $expense->delete();
        return $this->returnSuccessResponse(__('general.delete_success'));
    }

    public function show($id)
    {

        //check expense found in db
        $expense = Expense::find($id);
        if (!$expense)
            return $this->returnErrorResponse(__('general.found_error'));

        return $this->returnData(__('general.found_success'), 'data', $expense);
    }



    public function update($subdomain,Request $request, $id)
    {
        $rules = [
            'description' => 'required|string',
            'price' => 'required|numeric',
            'quantity' => 'nullable|integer',
        ];


        $this->validate($request, $rules);

        $expense = Expense::find($id);
        if (!$expense)
            return $this->returnErrorResponse(__('general.found_error'));

        $quantity = $request->quantity ?? 1;

        $expense->update([
            'description' => $request->input('description'),
            'price' => $request->input('price'),
            'quantity' => $quantity,
        ]);

        $updatedExpense = $expense->fresh();

        return $this->returnSuccessResponse(__('general.edit_success'), $updatedExpense);
    }

    public function filterExpensesWithProducts(Request $request)
    {
        $rules = [
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ];

        $validator = $this->validateRequest($request, $rules);

        if ($validator->fails()) {
            return $this->returnErrorResponse(__('general.validate_error'), $validator);
        }

        $from = Carbon::parse($request->from);
        $to = Carbon::parse($request->to);
        // Get expenses by date
        $expenses = Expense::whereDate('created_at', '>=', $from)
        ->whereDate('created_at', '<=', $to)
        ->select( "id","description", "price", "quantity")
        ->selectRaw("SUM(price * quantity) as total_price")
        ->groupBy("description", "price", "quantity", "id")
        ->get();

        // return $expenses;
        // Get product quantities by date

        $productQuantities = ProductQnty::whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->get();


        $collectionOfProducts = ProductQuantityResource::collection($productQuantities);
        $data = [];
        $data['expenses'] = $expenses;
        $data['products'] = $collectionOfProducts;
        return response()->json($data);
    }


    public function getTotalPaid(Request $request)
    {
        $rules = [
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ];

        $validator = $this->validateRequest($request, $rules);

        if ($validator->fails()) {
            return $this->returnErrorResponse(__('general.validate_error'), $validator);
        }

        $from = Carbon::parse($request->from);
        $to = Carbon::parse($request->to);
        // Get expenses by date
        $expenses = Expense::whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->select("description", "price", "quantity")
            ->selectRaw("SUM(price * quantity) as total_price")
            ->groupBy("description", "price", "quantity")
            ->get();
        $totalExpenses = $expenses->sum('total_price');

        // Get product quantities by date

        $productQuantities = ProductQnty::whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->get();

        $collectionOfProducts = TotalPaidResource::collection($productQuantities);


         $data = [];
        $data['total_expenses'] = $totalExpenses;
        $data['products'] = $collectionOfProducts;
        return response()->json($data);
    }
}
