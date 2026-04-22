<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DynamicPage extends Model
{
    protected $guarded = [];

    protected $hidden = ['created_at', 'updated_at','deleted_at', 'status'];
}
