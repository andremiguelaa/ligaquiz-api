<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Season extends Model
{
    protected $fillable = [
        'season'
    ];

    protected $hidden = [
        'id', 'created_at', 'updated_at'
    ];

    public function leagues()
    {
        return $this->hasMany('App\League', 'season', 'season');
    }

    public function rounds()
    {
        return $this->hasMany('App\Round', 'season', 'season');
    }

    public function games()
    {
        return $this->hasMany('App\Game', 'season', 'season');
    }
}
