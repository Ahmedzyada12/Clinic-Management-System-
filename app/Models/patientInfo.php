<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class patientInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'address',
        'date',
        'weight',
        'generated_id',
    ];

    //review!
    public function patient()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
