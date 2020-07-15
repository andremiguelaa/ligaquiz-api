<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Season extends Model
{
    protected $fillable = [
        'season'
    ];

    protected $hidden = [
        'created_at', 'updated_at'
    ];

    public function leagues()
    {
        return $this->hasMany('App\League');
    }

    public function rounds()
    {
        return $this->hasMany('App\Round');
    }
}
