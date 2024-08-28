<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductQuantityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'quantity' => $this->qnty,
            'cost' => $this->cost,
            'product_name'=>$this->product->name,
            'product_price'=>$this->product->price,
            'total_cost' => ($this->qnty * $this->product->price) + $this->cost,
        ];
    }
}
