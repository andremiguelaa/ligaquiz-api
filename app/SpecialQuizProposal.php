<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SpecialQuizProposal extends Model
{
    protected $fillable = [
        'user_id',
        'subject',
        'description',
        'content_1',
        'answer_1',
        'media_1_id',
        'content_2',
        'answer_2',
        'media_2_id',
        'content_3',
        'answer_3',
        'media_3_id',
        'content_4',
        'answer_4',
        'media_4_id',
        'content_5',
        'answer_5',
        'media_5_id',
        'content_6',
        'answer_6',
        'media_6_id',
        'content_7',
        'answer_7',
        'media_7_id',
        'content_8',
        'answer_8',
        'media_8_id',
        'content_9',
        'answer_9',
        'media_9_id',
        'content_10',
        'answer_10',
        'media_10_id',
        'content_11',
        'answer_11',
        'media_11_id',
        'content_12',
        'answer_12',
        'media_12_id',
    ];

    protected $hidden = [
        'created_at', 'updated_at'
    ];
}
