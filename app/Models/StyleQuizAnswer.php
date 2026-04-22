<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StyleQuizAnswer extends Model
{
    protected $fillable = ['user_id', 'question_id', 'option_id', 'text_answer'];

    protected $hidden = ['created_at' , 'updated_at'];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function question()
    {
        return $this->belongsTo(StyleQuizQuestion::class);
    }
    public function option()
    {
        return $this->belongsTo(StyleQuizOption::class);
    }
}
