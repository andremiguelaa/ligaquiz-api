<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Question;
use App\Http\Resources\Question as QuestionResource;
use App\Answer;

class Quiz extends Model
{
    protected $fillable = [
        'date',
        'question_ids',
    ];

    protected $casts = [
        'question_ids' => 'array',
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'laravel_through_key'
    ];

    public function getQuestions()
    {
        $questions = Question::whereIn('id', $this->question_ids)->get();
        return QuestionResource::collection($questions);
    }

    public function hasAnswers()
    {
        return boolval(Answer::where('quiz', 'quiz_'.$this->id)->first());
    }
}
