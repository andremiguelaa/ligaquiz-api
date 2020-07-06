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
        return $this->hasMany('App\QuizQuestion');
    }

    public function answers()
    {
        return Answer::whereIn(
            'question_id',
            $this->questions->pluck('question_id')->toArray()
        )->get();
    }
}
