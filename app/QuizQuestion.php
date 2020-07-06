<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class QuizQuestion extends Model
{
    protected $fillable = [
        'quiz_id',
        'question_id'
    ];

    protected $hidden = [
        'id', 'quiz_id', 'question_id', 'created_at', 'updated_at'
    ];

    public function question()
    {
        return $this->hasOne('App\Question', 'id', 'question_id');
    }
}
