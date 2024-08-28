<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductQnty extends Model
{
    use HasFactory;

    protected $table = 'product_qnties';

    protected $fillable = [
        'product_id',
        'qnty',
        'cost',
        'supplier_name'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
