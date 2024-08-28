<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Appointment;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
// use Spatie\Permission\Traits\HasRoles;

class User extends  Authenticatable implements JWTSubject
{
    use HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['id'];
    // protected $fillable = [
    //     'first_name',
    //     'last_name',
    //     'email',
    //     'password',
    // ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verified_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'permission' => 'array',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }


    /* patient relation One to One relation  */
    public function patient_info()
    {
        return $this->hasOne(patientInfo::class, 'user_id', 'id');
    }
    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'doctor_id'); // Assuming doctor_id is the foreign key
    }
    public function setting()
    {
        return $this->belongsTo(Setting::class, 'doctor_id');
    }

    /** patient may have alot of visits.*/
    public function visits()
    {
        return $this->hasMany(Visit::class, 'user_id', 'id');
    }
    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'doctor_id');
    }

    /*doctor relation one to many with appointment */
    // public function appointments()
    // {
    //     return $this->hasMany(Appointment::class, 'doctor_id',  'id');
    // }

    //return the amount based on the
    public static function getAmount($doctor_id, $type)
    {
        $user = User::where('id', $doctor_id)->where('role', 0)->first();
        if (isset($user)) {
            if ($type == 'diagnosis')
                $amount = $user->amount;
            else
                $amount = $user->follow_up;
        } else {
            $amount = -1;
        }

        return $amount;
    }


    public function specialization()
    {
        return $this->belongsTo(specialization::class, 'specialization_id', 'id');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function hasRole($role)
    {
        return $this->roles()->where('name', $role)->exists();
    }

    public function hasPermission($permission)
    {
        foreach ($this->roles as $role) {
            if ($role->permissions()->where('name', $permission)->exists()) {
                return true;
            }
        }
        return false;
    }
}