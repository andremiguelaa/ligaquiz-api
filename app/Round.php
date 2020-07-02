<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Round extends Model
{
    protected $fillable = [
        'round', 'season', 'date'
    ];

    protected $hidden = [
        'created_at', 'updated_at'
    ];
}
