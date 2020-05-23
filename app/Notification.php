<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'content',
        'type',
        'start_date',
        'end_date',
    ];

    protected $hidden = [
        'created_at', 'updated_at'
    ];
}
