<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IndividualQuizPlayer extends Model
{
    protected $fillable = [
        'name', 'surname', 'user_id'
    ];
}
