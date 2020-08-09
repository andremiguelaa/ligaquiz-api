<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Question;
use App\Answer;

class Quiz extends Model
{
    protected $fillable = [
        'date'
    ];

    protected $casts = [
        'question_ids' => 'array',
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'laravel_through_key'
    ];

    public function questions()
    {
        return $this->hasMany('App\QuizQuestion')->select('quiz_id', 'question_id');
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

    public function isSubmitted()
    {
        $questionIds = $this->questions()->get()->pluck('question_id')->toArray();
        return boolval(
            Answer::whereIn('question_id', $questionIds)->where('submitted', 1)->first()
        );
    }
}
