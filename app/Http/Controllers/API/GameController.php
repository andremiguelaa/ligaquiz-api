<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use Request;
use Validator;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Game;
use App\League;
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
                'season' => 'required_with:tier|exists:seasons,season|required_without_all:id,user',
                'tier' => [
                    'required_with:season',
                    'integer',
                    Rule::exists('leagues', 'tier')->where(function ($query) use ($input) {
                        $query->where('season', isset($input['season']) ? $input['season'] : 0);
                    })
                ],
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
                    } else if($key === 'tier'){
                        $users = League::where('season', $input['season'])
                            ->where('tier', $input['tier'])->first()->user_ids;
                        $query->whereIn('user_id_1', $users)->orWhereIn('user_id_2', $users);
                    } else {
                        $query->where($key, $value);
                    }
                }
            }
            $games = $query->get();
            $gamesQuizzes = $games->unique('quiz.questions')
                ->pluck('quiz.questions')
                ->filter();
            
            $answers = collect([]);
            $quizzes = $gamesQuizzes->map(function ($quiz) use (&$answers) {
                return $quiz->map(function ($question) use (&$answers) {
                    $answers = $answers->merge($question['question']->submitted_answers);
                    return $question['question'];
                });
            });
            $answers = $answers->map(function ($answer) {
                return $answer->only(['question_id', 'user_id', 'points', 'correct', 'corrected']);
            });

            $games = $games->map(function ($game) use ($answers) {
                $now = Carbon::now()->format('Y-m-d');
                if ($game->quiz && $now > $game->quiz->date) {
                    $questionIds = $game->quiz->questions->pluck('question_id')->toArray();
                    $game->answers = $answers
                        ->whereIn('user_id', [$game->user_id_1, $game->user_id_2])
                        ->whereIn('question_id', $questionIds)
                        ->groupBy(['user_id', 'question_id'])
                        ->map(function ($user) {
                            return $user->map(function ($question) {
                                return $question->map(function ($answer) {
                                    unset($answer['question_id']);
                                    unset($answer['user_id']);
                                    return $answer;
                                });
                            });
                        });
                    $game->user_id_1_game_points = 0;
                    $game->user_id_2_game_points = 0;
                    $game->user_id_1_league_points = 0;
                    $game->user_id_2_league_points = 0;
                    // todo: calculate game result
                }
                if ($game->quiz) {
                    $game->quiz->makeHidden('questions');
                }
                return $game;
            });

            return $this->sendResponse([
                'games' => $games,
                'quizzes' => array_values($quizzes->toArray()),
            ], 200);
        }
        
        return $this->sendError('no_permissions', [], 403);
    }
}
