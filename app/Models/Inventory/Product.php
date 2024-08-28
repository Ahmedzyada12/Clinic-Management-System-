<?php

namespace App\Models\inventory;

use App\Models\Inventory\ProductQnty;
use App\Models\Inventory\Category;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $table = 'products';

    protected $fillable = [
        'name',
        'type',
        'price',
        'description',
        'qnty',
        'alert_qty',
        'products_info_qty',
        'category_id',
    ];


    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function product_quantities()
    {
        return $this->hasMany(ProductQnty::class, 'product_id', 'id');
    }

    public function visits()
    {
        return $this->belongsToMany(Visit::class, 'visit_products', 'product_id', 'visit_id');
    }
}
