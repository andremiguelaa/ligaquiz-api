<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Storage;

class ExternalQuestion extends Model
{
    protected $fillable = [
        'used',
    ];

    public function getMediaAttribute($value)
    {
        return Storage::url('external/'.$value);
    }

    protected $hidden = [
        'created_at', 'updated_at'
    ];
}
