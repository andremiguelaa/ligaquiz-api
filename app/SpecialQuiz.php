<?php

namespace App;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Question;
use App\Answer;

class SpecialQuiz extends Model
{
    protected $fillable = [
        'date',
        'user_id',
        'subject',
        'description',
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'laravel_through_key'
    ];

    protected $appends = ['past'];

    public function getPastAttribute()
    {
        $now = Carbon::now()->format('Y-m-d');
        if($this->date < $now){
            return true;
        }
        return false;
    }

    public function questions()
    {
        return $this->hasMany('App\SpecialQuizQuestion');
    }

    public function hasAnswers()
    {
        return Answer::whereIn(
            'question_id',
            $this->questions->pluck('question_id')->toArray()
        )->count();
    }

    public function answers()
    {
        return Answer::whereIn(
            'question_id',
            $this->questions->pluck('question_id')->toArray()
        )->get();
    }

    public function isSubmitted()
    {
        $questionIds = $this->questions()->get()->pluck('question_id')->toArray();
        return boolval(
            Answer::whereIn('question_id', $questionIds)
                ->where('user_id', Auth::id())
                ->where('submitted', 1)
                ->first()
        );
    }
}
