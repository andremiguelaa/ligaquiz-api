<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Log extends Model
{
    protected $fillable = [
        'user_id', 'action'
    ];

    protected $appends = ['time'];

    public function getTimeAttribute()
    {
        return Carbon::createFromFormat('Y-m-d H:i:s', $this->created_at)->format('Y-m-d H:i:s');
    }

    protected $hidden = [
        'created_at', 'updated_at'
    ];
}
