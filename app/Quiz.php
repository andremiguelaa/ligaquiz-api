<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Question;

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
        return Question::whereIn('id', $this->question_ids)->get();
    }
}
