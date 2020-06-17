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
}
