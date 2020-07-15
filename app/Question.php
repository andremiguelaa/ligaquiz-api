<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = [
        'content',
        'answer',
        'media_id',
        'genre_id',
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'laravel_through_key'
    ];

    public function media()
    {
        return $this->hasOne('App\Media', 'id', 'media_id');
    }

    public function genre()
    {
        return $this->hasOne('App\Genre', 'id', 'genre_id');
    }

    /*
    public function percentage()
    {
        if ($this->submitted_answers->count()) {
            return $this->submitted_answers->where('correct', 1)->count() /
                $this->submitted_answers->count()
                * 100;
        }
        return 0;
    }
    */
}
