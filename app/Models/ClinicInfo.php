<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClinicInfo extends Model
{
    use HasFactory;
    protected $table = 'clinic_infos';
    protected $fillable = [
        'clinic_name_ar',
        'clinic_name_en',
        'clinic_bio_en',
        'clinic_bio_ar',
        'clinic_address_ar',
        'clinic_address_en',
        'clinic_department_ar',
        'clinic_department_en',
        'clinic_image',
        'doc_image',
        'logo',
        'clinic_fblink',
        'doctor_name',
        'doctor_email',
        'doctor_phone',
        'amount',

    ];
}
