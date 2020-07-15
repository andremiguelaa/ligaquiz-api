<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Round extends Model
{
    protected $fillable = [
        'round', 'season_id', 'date'
    ];

    protected $hidden = [
        'id', 'created_at', 'updated_at'
    ];
}
