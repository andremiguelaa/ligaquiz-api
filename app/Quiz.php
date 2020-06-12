<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Question;
use App\Http\Resources\Question as QuestionResource;

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
        'question_ids', 'created_at', 'updated_at'
    ];

    public function getQuestions()
    {
        $questions = Question::whereIn('id', $this->question_ids)->get();
        return QuestionResource::collection($questions);
    }
}
