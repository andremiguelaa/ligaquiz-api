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
        if ($value) {
            return Storage::url('external/'.$value);
        }
        return null;
    }

    protected $hidden = [
        'created_at', 'updated_at'
    ];
}
