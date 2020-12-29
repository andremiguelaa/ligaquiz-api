<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Request;
use Validator;
use App\Http\Controllers\BaseController as BaseController;
use App\Round;
use App\CupRound;
use App\CupGame;
use App\Quiz;
use App\Answer;
use App\Media;
use App\Traits\Cup as CupTrait;

class CupGameController extends BaseController
{
    use CupTrait;

    public function get(Request $request)
    {
        if (Auth::user()->hasPermission('quiz_play')) {
            $input = $request::all();
            $rules = [
                'date' => 'date_format:Y-m-d',
                'user' => 'required|exists:users,id',
                'opponent' => 'required|exists:users,id',
            ];
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            if (isset($input['date'])) {
                $round = Round::where('date', $input['date'])->first();
                if ($round) {
                    $cupRound = CupRound::where('round_id', $round->id)->first();
                }
                if (!isset($cupRound)) {
                    return $this->sendError('not_found', [], 404);
                }
                $cupGames = CupGame::with('cupRound.round.quiz.questions.question')
                    ->where('cup_round_id', $cupRound->id)
                    ->where('user_id_1', $input['user'])
                    ->where('user_id_2', $input['opponent'])
                    ->get();
                if (!$cupGames->count()) {
                    return $this->sendError('not_found', [], 404);
                }
                $quiz = Quiz::with('questions')
                    ->where('date', $input['date'])
                    ->first()
                    ->makeHidden(['id','past','today']);
                $questionIds = $quiz->questions->pluck('question_id')->toArray();
                $answers = Answer::whereIn('question_id', $questionIds)
                    ->where('submitted', 1)
                    ->select('user_id', 'question_id', 'cup_points', 'correct', 'corrected')
                    ->get()
                    ->makeHidden(['user_id','question_id','time']);
                $answersByQuestionId = $answers->groupBy(['question_id']);
                $groupedAnswers = $answers->groupBy(['question_id', 'user_id']);
                $quizzesByDate = [$input['date'] => array($quiz)];
                $this->getCupGameResults(
                    $cupGames,
                    $quizzesByDate,
                    $groupedAnswers
                );
                $response = $cupGames[0];
                $response->quiz = [
                    'date' => $quiz->date,
                    'questions' => $quiz
                        ->questions
                        ->map(function ($item) use ($answersByQuestionId) {
                            $question = $item->question;
                            if ($answersByQuestionId->count()) {
                                $question->percentage =
                                    $answersByQuestionId[$item->question_id]->where('correct', 1)->count() /
                                    $answersByQuestionId[$item->question_id]->count() *
                                    100;
                            }
                            return $question;
                        })
                ];
                $response->answers = $groupedAnswers->map(function ($question) use ($input) {
                    return [
                        $input['user'] => $question->get($input['user']) ?
                            $question->get($input['user'])[0] : null,
                        $input['opponent'] => $question->get($input['opponent']) ?
                            $question->get($input['opponent'])[0] : null,
                    ];
                });
                $mediaIds = array_filter($quiz->questions->pluck('question.media_id')->toArray());
                $response->media = array_reduce(
                    Media::whereIn('id', $mediaIds)->get()->toArray(),
                    function ($carry, $item) {
                        $mediaFile = $item;
                        unset($mediaFile['id']);
                        $carry[$item['id']] = $mediaFile;
                        return $carry;
                    },
                    []
                );
                unset($response->cupRound);
                return $this->sendResponse($response, 200);
            } else {
                $cupGames = CupGame::with('cupRound.round')
                    ->with('cup')
                    ->where(function ($userQuery) use ($input) {
                        $userQuery
                            ->where('user_id_1', $input['user'])
                            ->where('user_id_2', $input['opponent']);
                    })->orWhere(function ($userQuery) use ($input) {
                        $userQuery
                            ->where('user_id_1', $input['opponent'])
                            ->where('user_id_2', $input['user']);
                    })
                    ->get();
                $dates = $cupGames->pluck('cupRound.round.date')->toArray();
                $quizzes = Quiz::with('questions')->whereIn('date', $dates)->get();
                $questionIds = [];
                foreach ($quizzes as $quiz) {
                    $questionIds = array_merge(
                        $questionIds,
                        $quiz->questions->pluck('question_id')->toArray()
                    );
                }
                $answers = Answer::whereIn('question_id', $questionIds)
                    ->where('submitted', 1)
                    ->select('user_id', 'question_id', 'cup_points', 'correct', 'corrected')
                    ->get()
                    ->groupBy(['question_id', 'user_id']);
                $quizzesByDate = $quizzes->groupBy('date');
                $this->getCupGameResults(
                    $cupGames,
                    $quizzesByDate,
                    $answers
                );
                return $this->sendResponse($cupGames, 200);
            }
        }
        
        return $this->sendError('no_permissions', [], 403);
    }
}
