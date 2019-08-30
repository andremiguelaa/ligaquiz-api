<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IndividualQuiz extends Model
{
    protected $fillable = [
        'individual_quiz_type', 'date',
    ];
}
