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
        'cup_points',
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
        if ($this->created_at) {
            return Carbon::createFromFormat('Y-m-d H:i:s', $this->created_at)->format('Y-m-d H:i:s');
        }
    }
}
