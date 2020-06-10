<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SpecialQuiz extends Model
{
    protected $fillable = [
        'date',
        'question_ids',
        'user_id',
        'subject',
        'description',
    ];

    protected $hidden = [
        'created_at', 'updated_at'
    ];
}
