<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Storage;

class PaypalTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'url',
        'status',
        'period',
        'ammount'
    ];
}
