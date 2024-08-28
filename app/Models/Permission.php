<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = ['section', 'action'];
    // public function roles()
    // {
    //     return $this->belongsToMany(Role::class)->withPivot('allowed');
    // }
    public function roles()
    {
        return $this->belongsToMany(Role::class)->withPivot('create', 'read', 'update', 'delete','type');
    }
}