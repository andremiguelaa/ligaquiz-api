<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SpecialQuizQuestion extends Model
{
    protected $fillable = [
        'special_quiz_id',
        'question_id'
    ];
}
