<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $fillable = [
        'user_id', 'action'
    ];

    protected $hidden = [
        'id', 'updated_at'
    ];
}
