<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StyleQuizOption extends Model
{
    protected $fillable = ['question_id', 'option_text'];

    protected $hidden = ['created_at' , 'updated_at'];
    public function question()
    {
        return $this->belongsTo(StyleQuizQuestion::class);
    }
}
