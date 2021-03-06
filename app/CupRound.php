<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CupRound extends Model
{
    protected $fillable = [
        'cup_id', 'cup_round', 'round_id'
    ];

    protected $hidden = [
        'created_at', 'updated_at'
    ];

    public function games()
    {
        return $this->hasMany('App\CupGame');
    }

    public function round()
    {
        return $this->hasOne('App\Round', 'id', 'round_id');
    }

    public function cup()
    {
        return $this->hasOne('App\Cup', 'id', 'cup_id');
    }
}
