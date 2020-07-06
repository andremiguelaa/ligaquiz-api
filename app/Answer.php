<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    protected $fillable = [
        'question_id',
        'user_id',
        'text',
        'points',
        'correct',
        'corrected',
        'submitted',
    ];

    protected $hidden = [
        'created_at', 'updated_at'
    ];
}
