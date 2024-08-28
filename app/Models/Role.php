<?php

namespace App\Models;

use App\Models\admin\Assistant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class)->withPivot('create', 'read', 'update', 'delete', 'type');
    }
    public function users()
    {
        return $this->belongsToMany(User::class);
    }
    public function patients()
    {
        return $this->belongsToMany(Patient::class, 'patient_role');
    }
    public function assistants()
    {
        return $this->hasMany(Assistant::class);
    }
}
