<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use Request;
use Validator;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Season;
use App\Quiz;
use App\SpecialQuiz;
use App\Answer;

class AnswerController extends BaseController
{
    public function get(Request $request)
    {
        if (
            Auth::user()->hasPermission('quiz_play') ||
            Auth::user()->hasPermission('specialquiz_play') ||
            Auth::user()->hasPermission('answer_correct')
        ) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'season' => 'required_without_all:special_quiz,quiz|exists:seasons,season',
                'quiz' => 'required_without_all:special_quiz,season|exists:quizzes,id',
                'special_quiz' => 'required_without_all:quiz,season|exists:special_quizzes,id',
                'submitted' => 'boolean'
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            if (
                !Auth::user()->hasPermission('answer_correct') &&
                !Auth::user()->hasPermission('quiz_play') && isset($input['quiz']) ||
                !Auth::user()->hasPermission('specialquiz_play') && isset($input['special_quiz'])
            ) {
                return $this->sendError('no_permissions', [], 403);
            }
            $startOfDay = Carbon::now()->startOfDay();
            if (isset($input['season'])) {
                $quizDates = Season::with('rounds')
                    ->where('season', $input['season'])
                    ->first()
                    ->rounds
                    ->pluck('date')
                    ->toArray();
                $quizQuestions = Quiz::with('questions')
                    ->whereIn('date', $quizDates)
                    ->get()
                    ->pluck('questions');
                $questionIds = $quizQuestions->reduce(function ($carry, $item) {
                    return array_merge($carry, $item->pluck('question_id')->toArray());
                }, []);
                $answers = Answer::whereIn('question_id', $questionIds)
                    ->select('user_id', 'question_id', 'submitted', 'correct', 'created_at')
                    ->get();
            } elseif (isset($input['quiz'])) {
                $answers = Quiz::with('questions')->find($input['quiz'])->answers();
            } elseif (isset($input['special_quiz'])) {
                $answers = SpecialQuiz::with('questions')->find($input['special_quiz'])->answers();
            }
            if (isset($input['mine'])) {
                $answers = $answers->where('user_id', Auth::id());
            }
            if (
                !Auth::user()->hasPermission('answer_correct') && !isset($input['mine']) ||
                isset($input['season'])
            ) {
                $answers = $answers->where('created_at', '<', $startOfDay);
                $answers->makeHidden('text');
            }
            if (isset($input['submitted'])) {
                $answers = $answers->where('submitted', $input['submitted']);
                $answers->makeHidden('submitted');
            }
            return $this->sendResponse($answers->groupBy('question_id'), 200);
        }
        return $this->sendError('no_permissions', [], 403);
    }

    public function create(Request $request)
    {
        if (
            Auth::user()->hasPermission('quiz_play') ||
            Auth::user()->hasPermission('specialquiz_play')
        ) {
            $quiz = null;
            $specialQuiz = null;
            $questionIds = [];
            $now = Carbon::now()->format('Y-m-d');
            if (Auth::user()->hasPermission('quiz_play')) {
                $quiz = Quiz::with('questions')->where('date', $now)->first();
                if ($quiz) {
                    $questionIds = array_merge(
                        $questionIds,
                        $quiz->questions->pluck('question_id')->toArray()
                    );
                }
            }
            if (Auth::user()->hasPermission('specialquiz_play')) {
                $specialQuiz = SpecialQuiz::with('questions')->where('date', $now)->first();
                if ($specialQuiz) {
                    $questionIds = array_merge(
                        $questionIds,
                        $specialQuiz->questions->pluck('question_id')->toArray()
                    );
                }
            }
            if (!$quiz && !$specialQuiz) {
                return $this->sendError('no_quiz_today', null, 400);
            }
            $input = $request::all();
            $validator = Validator::make($input, [
                'question_id' => [
                    'exists:questions,id',
                    Rule::in($questionIds)
                ],
                'text' => 'string',
                'points' => 'integer|min:0|max:3',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $newAnswer = [
                'question_id' => intval($input['question_id']),
                'user_id' => Auth::id(),
                'text' => $input['text'],
                'points' => isset($input['points']) ? $input['points'] : -1,
                'correct' => 0,
                'corrected' => 0,
                'submitted' => 0,
            ];
            $answer = Answer::create($newAnswer);
            return $this->sendResponse($input, 201);
        }
        return $this->sendError('no_permissions', [], 403);
    }

    public function update(Request $request)
    {
        if (Auth::user()->hasPermission('answer_correct')) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'id' => 'required|exists:answers,id',
                'correct' => 'required|boolean'
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $answer = Answer::find($input['id']);
            $answer->correct = $input['correct'];
            $answer->corrected = true;
            $answer->save();
            return $this->sendResponse($answer, 201);
        }
        return $this->sendError('no_permissions', [], 403);
    }
}
