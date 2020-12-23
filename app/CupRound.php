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
}
