<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ExternalQuestion extends Model
{
    protected $fillable = [
        'used',
    ];

    protected $hidden = [
        'created_at', 'updated_at'
    ];
}
