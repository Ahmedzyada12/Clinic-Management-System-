<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Patient extends Authenticatable implements JWTSubject
{
    use HasFactory;
    use HasApiTokens;
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'image',
        'password',
        'doctor_id',
        'role_id',
        // 'role',
    ];

    // If you need to define relationships, you can add them here.
    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
    public function vitalHistories()
    {
        return $this->hasMany(VitalHistory::class);
    }
    protected $hidden = [
        'password', 'remember_token',
    ];

    // Get the identifier for the JWT.
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}