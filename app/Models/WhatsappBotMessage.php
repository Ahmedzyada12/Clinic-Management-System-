<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappBotMessage extends Model
{
    use HasFactory;
    
    public static $BOOK_OPERATION = 'book';
    public static $INQUIRY_OPERATION = 'inquiry';
    public static $REGISTER_OPERATION = 'register';
    public static $UNKNOWN_OPERATION = 'unknown';
    protected $table = 'whatsapp_bot_messages';
    protected $fillable = [
        'profile_name',
        'number',
        'message',
        'extra_date',
        'operation',
    ];
}
