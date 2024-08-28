<?php

namespace App\Models\Inventory;

use App\Models\Inventory\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    protected $table = "categories";

    protected $fillable = [
        'name_ar',
        'name_en',
    ];


    public function products()
    {
        return $this->hasMany(Product::class, 'category_id', 'id');
    }

}
