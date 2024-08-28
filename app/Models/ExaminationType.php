<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExaminationType extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'amount', 'color', 'doctor_id'];

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }
    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'examination_type_id');
    }
}
