<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IndividualQuizResult extends Model
{
    protected $fillable = [
        'individual_quiz_id', 'individual_quiz_player_id', 'result'
    ];
}
