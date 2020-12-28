<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Cup as CupTrait;

class Cup extends Model
{
    use CupTrait;

    protected $fillable = [
        'season_id', 'tiebreakers'
    ];

    protected $hidden = [
        'created_at', 'updated_at'
    ];

    protected $casts = [
        'tiebreakers' => 'array',
    ];

    public function rounds()
    {
        return $this->hasMany('App\CupRound');
    }

    public function getData()
    {
        return $this->getRoundsResults($this);
    }
}
