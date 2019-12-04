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
            $results = IndividualQuizResult::where('individual_quiz_id', $this->id)
                ->select('individual_quiz_player_id', 'result')
                ->get()->toArray();
            $maxResult = max(array_map(function ($result) {
                return $result['result'];
            }, $results));
            $this->results = array_map(function ($result) use ($maxResult) {
                $result['score'] = $result['result'] / $maxResult * 100;
                return $result;
            }, $results);
        }
        return $this->results;
    }
}
