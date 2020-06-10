<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = [
        'content',
        'answer',
        'media',
        'genre_id',
    ];

    protected $hidden = [
        'created_at', 'updated_at'
    ];
}
