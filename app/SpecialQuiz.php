<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Question;
use App\Answer;

class SpecialQuiz extends Model
{
    protected $fillable = [
        'date',
        'user_id',
        'subject',
        'description',
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'laravel_through_key'
    ];

    public function questions()
    {
        return $this->hasMany('App\SpecialQuizQuestion');
    }

    public function hasAnswers()
    {
        return Answer::whereIn(
            'question_id',
            $this->questions->pluck('question_id')->toArray()
        )->count();
    }

    public function answers()
    {
        return Answer::whereIn(
            'question_id',
            $this->questions->pluck('question_id')->toArray()
        )->get();
    }
}
