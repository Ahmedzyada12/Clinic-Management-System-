<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;

class Appointment extends Model
{
    use HasFactory;
    public $timestamps = true;
    protected $table = 'appointmentss';
    protected $fillable = ['doctor_id', 'time_start', 'time_end', 'duration', 'status'];

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
    public function examination_type()
    {
        return $this->belongsTo(ExaminationType::class, 'examination_type_id');
    }
    public function examinationType()
    {
        return $this->belongsTo(ExaminationType::class, 'examination_id');
    }
}
