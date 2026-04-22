<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StyleQuizQuestion extends Model
{
    protected $fillable = ['question_text', 'active'];

    protected $hidden = ['created_at' , 'updated_at'];

    public function options()
    {
        return $this->hasMany(StyleQuizOption::class , 'question_id');
    }
    public function answers()
    {
        return $this->hasMany(StyleQuizAnswer::class);
    }
}
