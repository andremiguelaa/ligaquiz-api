<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\CupGameResults;

class Cup extends Model
{
    use CupGameResults;

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

    public function getData()
    {
        return $this->getRoundResults($this->rounds);
    }
}
