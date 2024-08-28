<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $table = 'reservations';
    protected $fillable = [
        'patient_id',
        'appointment_id',
        'status',
        'examination_id',
        // 'doctor_id'
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function examination_type()
    {
        return $this->belongsTo(ExaminationType::class, 'examination_id');
    }
    public function examination()
    {
        return $this->belongsTo(ExaminationType::class, 'examination_id');
    }
    public function payment()
    {
        return $this->hasOne(Payment::class,);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }
}
