<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialLink extends Model
{
    protected $guarded = [];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    // icon er jonno full url return korbe
    public function getIconAttribute($value)
    {
        if ($value) {
            return asset($value);
        }
        return null;
    }
}
