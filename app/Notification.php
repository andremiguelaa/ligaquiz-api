<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NationalRanking extends Model
{
    protected $fillable = [
        'content',
        'type',
        'start_date',
        'end_date',
    ];
}
