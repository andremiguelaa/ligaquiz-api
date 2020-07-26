<?php

namespace App;

use App\Question;
use Illuminate\Database\Eloquent\Model;
use Storage;

class Media extends Model
{
    protected $fillable = [
        'filename',
        'type'
    ];

    protected $hidden = [
        'filename', 'created_at', 'updated_at'
    ];

    protected $appends = ['url'];

    public function getUrlAttribute()
    {
        if ($this->filename) {
            return Storage::url($this->filename . '?' . strtotime($this->updated_at));
        }

        return null;
    }

    public function isUsed()
    {
        return boolval(Question::where('media_id', $this->id)->first());
    }
}
