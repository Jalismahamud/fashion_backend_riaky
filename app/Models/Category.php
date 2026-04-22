<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name'];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $baseSlug = Str::slug($model->name);
                $slug = $baseSlug;

                if (self::where('slug', $slug)->exists()) {
                    $random = rand(1000, 9999);
                    $slug = $baseSlug . '-' . $random;
                    // Ensure uniqueness if needed
                    while (self::where('slug', $slug)->exists()) {
                        $random = rand(1000, 9999);
                        $slug = $baseSlug . '-' . $random;
                    }
                }
                $model->slug = $slug;
            }
        });
    }


    public function items()
    {
        return $this->hasMany(Item::class, 'category_id', 'id');
    }
}
