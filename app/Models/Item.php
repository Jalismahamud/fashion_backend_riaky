<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'clouth_type',
        'material',
        'pattern',
        'color',
        'season',
        'item_name',
        'image',
        'image_path',
        'buying_info',
        'site_link',
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $baseSlug = $model->item_name ? Str::slug($model->item_name) : 'item';
                $slug = $baseSlug;

                if (self::where('slug', $slug)->exists()) {
                    $random = rand(1000, 9999);
                    $slug = $baseSlug . '-' . $random;

                    while (self::where('slug', $slug)->exists()) {
                        $random = rand(1000, 9999);
                        $slug = $baseSlug . '-' . $random;
                    }
                }
                $model->slug = $slug;
            }
        });
    }


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }
}
