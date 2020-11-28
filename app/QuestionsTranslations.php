<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class QuestionsTranslations extends Model
{
    protected $fillable = [
        'question_id',
        'content',
        'answer',
        'user_id',
    ];

    protected $hidden = [
        'created_at', 'updated_at'
    ];
}
