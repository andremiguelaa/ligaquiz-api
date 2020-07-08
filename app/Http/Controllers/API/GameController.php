<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use Request;
use Validator;
use Carbon\Carbon;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Game;
use App\Question;
use App\QuizQuestion;
use App\Answer;

class GameController extends BaseController
{
    public function get(Request $request)
    {
        if (Auth::user()->hasPermission('quiz_play')) {
            $input = $request::all();
            $rules = [
                'id' => 'exists:games,id|required_without_all:season,user',
                'season' => 'required_with:round|exists:seasons,season|required_without_all:id,user',
                'round' => 'integer|between:1,20',
                'user' => 'exists:users,id|required_without_all:id,season|required_with:opponent',
                'opponent' => 'exists:users,id'
            ];
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $query = Game::with(
                'quiz',
                'quiz.questions',
                'quiz.questions.question',
                'quiz.questions.question.submitted_answers'
            );
            foreach ($input as $key => $value) {
                if (in_array($key, array_keys($rules))) {
                    if ($key === 'user' || $key === 'opponent') {
                        $query->where(function ($userQuery) use ($value) {
                            $userQuery->where('user_id_1', $value)->orWhere('user_id_2', $value);
                        });
                    } else {
                        $query->where($key, $value);
                    }
                }
            }
            $games = $query->get();
            $gamesQuizzes = $games->unique('quiz.questions')
                ->pluck('quiz.questions')
                ->filter();
            
            $questionIds = [];

            $quizzes = $gamesQuizzes->map(function ($quiz) use (&$questionIds) {
                return $quiz->map(function ($question) use (&$questionIds) {
                    array_push($questionIds, $question['question']['id']);
                    return $question['question'];
                });
            });

            $answers = Answer::whereIn('question_id', $questionIds)
                ->where('submitted', 1)
                ->select('question_id', 'user_id', 'points', 'correct', 'corrected')
                ->get();

            $games = $games->map(function ($game) {
                $now = Carbon::now()->format('Y-m-d');
                if ($game->quiz && $now > $game->quiz->date) {
                    // todo: calculate game result
                    // dd($game->result());
                }
                if ($game->quiz) {
                    $game->quiz->makeHidden('questions');
                }
                return $game;
            });

            return $this->sendResponse([
                'games' => $games,
                'quizzes' => $quizzes,
                'answers' => $answers
            ], 200);
        }
        
        return $this->sendError('no_permissions', [], 403);
    }
}
