<?php

namespace App\Models\Patient;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseHistory extends Model
{
    use HasFactory;
    protected $table= 'case_histories';

    protected $fillable = [
        'patient_id',
        'title',
        'description',
    ];
}
