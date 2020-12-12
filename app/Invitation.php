<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Invitation extends Model
{
    protected $fillable = [
        'user_id', 'email'
    ];
}
