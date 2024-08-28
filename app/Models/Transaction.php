<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;
    protected $fillable = [
        'invoice_id',
        'invoice_status',
        'invoice_reference',
        'invoice_value',
        'due_deposit',
        'deposit_status',
        'invoice_display_value',
        'customer_name',
        'customer_mobile',
        'customer_email',
        'customer_reference',
        'transaction_id',
        'payment_gateway',
        'transaction_status',
        'transaction_date',
        'reference_id',
        'track_id',
        'authorization_id',
        'transaction_value',
        'paid_currency',
        'paid_currency_value',
        'total_service_charge',
        'vat_amount',
        'ip_address',
        'country',
        'invoice_error',
        'error_code',
    ];
}
