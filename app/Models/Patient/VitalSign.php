<?php

namespace App\Models\Patient;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VitalSign extends Model
{
    use HasFactory;
    
    protected $table = 'vital_signs';

    protected $fillable = [
        'patient_id',
        'heart_rate',
        'systolic_blood_pressure',
        'diastolic_blood_pressure',
        'temperature',
        'oxygen_saturation',
        'respiratory_rate',
        'bmi_weight',
        'bmi_height'
    ];
}
