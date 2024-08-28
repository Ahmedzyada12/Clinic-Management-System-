<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Inventory\Product;

class Visit extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'doctor_id',
        'appointment_id',
        'status',       //inserted by default
        'description',  //provided by doctor
        'file',         //provided by doctor
        'amount',       //provided by assistant
        'extra_amount', ////provided by assistant
        'type'
    ];


    /** the visit have only one patient.*/
    public function patient()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /** the visit have only one apponintment */
    public function appointment()
    {
        return $this->hasOne(Appointment::class, 'id', 'appointment_id');
    }


    public function products()
    {
        return $this->belongsToMany(Product::class, 'visit_products', 'visit_id', 'product_id');
    }


        /** the visit have only one doctor.*/
        public function doctor()
        {
            return $this->belongsTo(User::class, 'doctor_id', 'id');
        }

}
