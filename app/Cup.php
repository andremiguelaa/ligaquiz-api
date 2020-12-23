<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Cup extends Model
{
    protected $fillable = [
        'season_id', 'user_ids'
    ];

    protected $hidden = [
        'created_at', 'updated_at'
    ];

    protected $casts = [
        'user_ids' => 'array',
    ];

    public function rounds()
    {
        return $this->hasMany('App\CupRound');
    }
}
