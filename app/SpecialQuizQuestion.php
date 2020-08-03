<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SpecialQuizQuestion extends Model
{
    protected $fillable = [
        'special_quiz_id',
        'question_id'
    ];

    protected $hidden = [
        'id', 'created_at', 'updated_at'
    ];

    public function question()
    {
        return $this->hasOne('App\Question', 'id', 'question_id');
    }
}
