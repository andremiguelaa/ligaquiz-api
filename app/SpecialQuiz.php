<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Question;
use App\Http\Resources\Question as QuestionResource;
use App\Answer;

class SpecialQuiz extends Model
{
    protected $fillable = [
        'date',
        'question_ids',
        'user_id',
        'subject',
        'description',
    ];

    protected $casts = [
        'question_ids' => 'array',
    ];

    protected $hidden = [
        'created_at', 'updated_at'
    ];

    public function getQuestions()
    {
        $questions = Question::whereIn('id', $this->question_ids)->get();
        return QuestionResource::collection($questions);
    }

    public function hasAnswers()
    {
        return boolval(Answer::where('quiz', 'special_quiz_'.$this->id)->first());
    }
}
