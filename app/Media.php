<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $fillable = [
        'filename',
        'type'
    ];

    protected $hidden = [
        'created_at', 'updated_at'
    ];
}
