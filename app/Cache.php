<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Cache extends Model
{
    protected $fillable = [
        'type', 'identifier', 'value'
    ];

    protected $casts = [
        'value' => 'array',
    ];
}
