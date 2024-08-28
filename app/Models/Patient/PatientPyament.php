<?php

namespace App\Models\Patient;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientPyament extends Model
{
    use HasFactory;
    protected $table = "patient_pyaments";

    protected $fillable = [
        'visit_id',
        'patient_id',
        'order_id',
        'amount',
        'status'
    ];
}
