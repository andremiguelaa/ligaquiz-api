<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $fillable = [
        'round_id', 'user_id_1', 'user_id_2'
    ];

    protected $hidden = [
        'created_at', 'updated_at'
    ];

    public function round()
    {
        return $this->hasOne('App\Round', 'id', 'round_id');
    }
}
