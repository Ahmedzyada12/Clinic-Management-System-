<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientType extends Model
{
    use HasFactory;
    
    protected $table = "patient_types";

    protected $fillable = [
        'name_ar',
        'name_en',
    ];

    //review!
    // public function patient()
    // {
    //     return $this->hasOne(User::class, 'id', 'user_id');
    // }
}
