<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service_Blog extends Model
{
    use HasFactory;
    protected $table="services_blogs";
    protected $fillable=['title','description','type','image'];

}
