<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IndividualQuiz extends Model
{
    protected $fillable = [
        'individual_quiz_type', 'date',
    ];

    protected $results;

    public function getResultsAttribute()
    {
        if (!isset($this->results)) {
            $this->results = IndividualQuizResult::where('individual_quiz_id', $this->id)
                ->select('individual_quiz_player_id', 'result')
                ->get();
        }
        return $this->results;
    }
}
