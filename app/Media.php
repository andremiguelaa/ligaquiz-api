<?php

namespace App;

use App\Question;
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

    public function isUsed()
    {
        return boolval(Question::where('media_id', $this->id)->first());
    }
}
