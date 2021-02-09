<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Cache extends Model
{
    protected $fillable = [
        'type', 'identifier', 'value', 'created_at', 'updated_at'
    ];

    protected $casts = [
        'value' => 'array',
    ];
}
