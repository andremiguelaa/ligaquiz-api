<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Answer extends Model
{
    protected $fillable = [
        'question_id',
        'user_id',
        'text',
        'points',
        'correct',
        'corrected',
        'submitted',
    ];

    protected $hidden = [
        'created_at', 'updated_at'
    ];

    protected $appends = ['time'];

    public function getTimeAttribute()
    {
        return Carbon::createFromFormat('Y-m-d H:i:s', $this->created_at)->format('Y-m-d H:i:s');
    }
}
