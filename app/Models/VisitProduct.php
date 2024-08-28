<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitProduct extends Model
{
    use HasFactory;
    protected $table='visit_products';
    protected $fillable=['visit_id','product_id','quantity'];
    
    
    public function visit(){
        return $this->belongsTo(Visit::class,'visit_id');
    }

}
