<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    protected $fillable = [
        'date',
        'question_ids',
    ];

    protected $hidden = [
        'created_at', 'updated_at'
    ];
}
