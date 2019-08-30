<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IndividualQuizPlayer extends Model
{
    protected $fillable = [
        'individual_quiz_type', 'date'
    ];
}
