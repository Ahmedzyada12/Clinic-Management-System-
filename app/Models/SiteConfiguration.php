<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SiteConfiguration extends Model
{
    use HasFactory;
    protected $table = "site__configurtations";
    public $timestamps = true;
    
    // protected $fillable = [
    //     'logo',
    //     'text_logo',
    //     'cover',
    //     'second_cover',
    //     'text_cover',
    //     'main_background_color',
    //     'secondry_background_color',
    //     'first_text_color',
    //     'second_text_color',
    //     'slogan_title',
    //     'slogan_body',
    //     'sections',
    //     'phone',
    //     'address',
    //     'social_media_links',
    //     'video_url',
    //     'social_analytics'
    // ];
    protected $guarded = [];

    protected $casts = [
        'phone' => 'array',
        'address' => 'array',
        'social_media_links' => 'array',
        'social_analytics' => 'array',
    ];


    // // Accessor for the "logo" attribute
    // public function getLogoAttribute()
    // {
    //     $logo = $this->attributes['logo'] ?? null;

    //     // If 'logo' is actually a URL or a full path, you might return it directly
    //     return $logo ? 'users/assistants/'.$logo : null;
    // }

    // // Accessor for the "cover" attribute
    // public function getCoverAttribute()
    // {
    //     $cover = $this->attributes['cover'] ?? null;
    //     // If 'cover' is actually a URL or a full path, you might return it directly
    //     return $cover ?'users/assistants/'.$cover: null;
    // }

    // public function getSecondCoverAttribute()
    // {
    //     $cover = $this->attributes['second_cover'] ?? null;
    //     // If 'cover' is actually a URL or a full path, you might return it directly
    //     return $cover ?'users/assistants/'. $cover: null;
    // }
}
