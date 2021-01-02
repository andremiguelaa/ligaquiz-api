<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CupGame extends Model
{
    protected $fillable = [
        'cup_round_id', 'user_id_1', 'user_id_2'
    ];

    protected $hidden = [
        'created_at', 'updated_at'
    ];

    public function cupRound()
    {
        return $this->hasOne('App\CupRound', 'id', 'cup_round_id');
    }
}
