<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Round extends Model
{
    protected $fillable = [
        'round', 'season_id', 'date'
    ];

    protected $hidden = [
        'created_at', 'updated_at'
    ];

    public function quiz()
    {
        return $this->hasOne('App\Quiz', 'date', 'date');
    }
}
