<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\IndividualQuiz;

class IndividualQuizResult extends Model
{
    protected $fillable = [
        'individual_quiz_id', 'individual_quiz_player_id', 'result'
    ];

    public function individual_quiz()
    {
        return $this->hasOne('App\IndividualQuiz', 'id', 'individual_quiz_id');
    }
}
