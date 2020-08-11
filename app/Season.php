<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Season extends Model
{
    protected $fillable = [
        'season'
    ];

    protected $hidden = [
        'created_at', 'updated_at'
    ];

    protected $appends = ['past'];

    public function getPastAttribute()
    {
        $now = Carbon::now()->format('Y-m-d');
        if ($now >= $this->rounds->first()->date) {
            return true;
        }
        return false;
    }

    public function leagues()
    {
        return $this->hasMany('App\League');
    }

    public function rounds()
    {
        return $this->hasMany('App\Round');
    }
}
