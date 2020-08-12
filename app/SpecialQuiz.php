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
        return $this->hasMany('App\SpecialQuizQuestion')->select('special_quiz_id', 'question_id');
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

    public function getSubmittedAnswers()
    {
        return Answer::whereIn(
            'question_id',
            $this->questions->pluck('question_id')->toArray()
        )
        ->where('submitted', 1)
        ->get();
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

    public function getResult()
    {
        $answers = $this->getSubmittedAnswers();
        if ($answers->count() !== $answers->where('corrected', 1)->count()) {
            return null;
        }
        $questionStatistics = $answers->reduce(function ($carry, $item) {
            if (!isset($carry[$item->question_id])) {
                $carry[$item->question_id] = (object) [
                    'correct' => 0,
                    'total' => 0,
                    'bonus' => 0,
                    'percentage' => 0
                ];
            }
            if ($item->correct) {
                $carry[$item->question_id]->correct++;
            }
            $carry[$item->question_id]->total++;
            $carry[$item->question_id]->bonus = intval(
                100 - (
                    $carry[$item->question_id]->correct / $carry[$item->question_id]->total * 100
                )
            );
            $carry[$item->question_id]->percentage =
                100 * $carry[$item->question_id]->correct / $carry[$item->question_id]->total;
            return $carry;
        }, []);
        $results = $answers->reduce(function ($carry, $item) use ($questionStatistics) {
            if (!isset($carry[$item->user_id])) {
                $carry[$item->user_id] = (object) [
                    'user_id' => $item->user_id,
                    'questions' => (object) [],
                    'score' => 0
                ];
            }
            $carry[$item->user_id]->questions->{$item->question_id} = (object) [
                'points' => 0,
                'joker' => false
            ];
            if ($item->points) {
                $carry[$item->user_id]->questions->{$item->question_id}->joker = true;
            }
            if ($item->correct) {
                $bonus = $item->points ? $questionStatistics[$item->question_id]->bonus : 0;
                $carry[$item->user_id]->questions->{$item->question_id}->points = 20 + $bonus;
            }
            if (!$item->correct && $item->points) {
                $carry[$item->user_id]->questions->{$item->question_id}->points = -20;
            }
            $carry[$item->user_id]->score +=
                $carry[$item->user_id]->questions->{$item->question_id}->points;
            return $carry;
        }, []);
        usort($results, function ($a, $b) {
            return $b->score - $a->score;
        });
        $rank = 1;
        foreach ($results as $key => $player) {
            if (!($key > 0 && $results[$key - 1]->score === $results[$key]->score)) {
                $rank = $key + 1;
            }
            $results[$key]->rank = $rank;
        }
        return (object) [
            'question_statistics' => $questionStatistics,
            'ranking' => $results,
        ];
    }
}
