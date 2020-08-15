<?php

namespace App;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Question;
use App\Answer;

class Quiz extends Model
{
    protected $fillable = [
        'date'
    ];

    protected $casts = [
        'question_ids' => 'array',
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'laravel_through_key'
    ];

    protected $appends = ['past', 'today'];

    public function getPastAttribute()
    {
        $now = Carbon::now()->format('Y-m-d');
        if ($this->date < $now) {
            return true;
        }
        return false;
    }

    public function getTodayAttribute()
    {
        $now = Carbon::now()->format('Y-m-d');
        if ($this->date === $now) {
            return true;
        }
        return false;
    }

    public function questions()
    {
        return $this->hasMany('App\QuizQuestion')->select('quiz_id', 'question_id');
    }

    public function hasAnswers()
    {
        return Answer::whereIn(
            'question_id',
            $this->questions->pluck('question_id')->toArray()
        )->count();
    }

    public function getAnswers()
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

    public function isCompleted()
    {
        $questionIds = $this->questions()->get()->pluck('question_id')->toArray();
        $questions = Question::whereIn('id', $questionIds)->get();
        $completed = true;
        foreach ($questions as $question) {
            if (
                !boolval($question->content) ||
                !boolval($question->answer) ||
                !boolval($question->genre_id)
            ) {
                $completed = false;
            }
        }
        return $completed;
    }
}
