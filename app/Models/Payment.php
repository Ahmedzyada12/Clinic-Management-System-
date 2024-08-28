<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'amount',
        'extra_amount',
        'total',
        'status',
        'comment',
        'reservation_id',
        'payment_method'
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }
}
