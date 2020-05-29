<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\IndividualQuizResult;

class IndividualQuizPlayer extends Model
{
    protected $fillable = [
        'name', 'surname', 'user_id'
    ];

    public function individual_quiz_results()
    {
        return $this->hasMany('App\IndividualQuizResult');
    }
}
