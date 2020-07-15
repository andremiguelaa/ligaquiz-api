<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $fillable = [
        'season', 'round', 'user_id_1', 'user_id_2'
    ];

    protected $hidden = [
        'created_at', 'updated_at'
    ];
}
