<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    // protected $table = 'settings';

    // protected $primaryKey = 'doctor_id';

    protected $fillable = ['doctor_id', 'api_key_myfatoorah'];
    // protected $casts = [
    //     'api_key_myfatoorah' => 'array', // This will automatically handle JSON encoding/decoding
    // ];
    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }
}