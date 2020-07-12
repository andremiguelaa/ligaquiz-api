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
                    } elseif ($key === 'tier') {
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
                ->pluck('quiz.questions', 'quiz.id')
                ->filter();
            
            $answers = collect([]);
            $quizzes = $gamesQuizzes->map(function ($quiz) use (&$answers) {
                return $quiz->map(function ($question) use (&$answers) {
                    $answers = $answers->merge($question['question']->submitted_answers);
                    $question['question']->makeHidden('submitted_answers');
                    return $question['question'];
                });
            });
            $answers = $answers->map(function ($answer) {
                return $answer->only(['question_id', 'user_id', 'points', 'correct', 'corrected']);
            });

            $games = $games->map(function ($game) use ($input, $answers) {
                $now = Carbon::now()->format('Y-m-d');
                if ($game->quiz && $now > $game->quiz->date) {
                    $questionIds = $game->quiz->questions->pluck('question_id')->toArray();
                    $gameAnswers = $answers
                        ->whereIn('user_id', [$game->user_id_1, $game->user_id_2])
                        ->whereIn('question_id', $questionIds)
                        ->sortBy('question_id')
                        ->groupBy('user_id')->map(function ($user) {
                            return $user->map(function ($answer) {
                                unset($answer['user_id']);
                                return $answer;
                            });
                        });
                    
                    $game->solo = $game->user_id_1 === $game->user_id_2;
                    $game->user_id_1_correct_answers = 0;
                    $game->user_id_1_game_points = 0;
                    
                    if (!isset($gameAnswers[$game->user_id_1])) {
                        $game->user_id_1_game_points = 'F';
                    } elseif ($gameAnswers[$game->user_id_1]->where('corrected', 0)->count()) {
                        $game->user_id_1_game_points = 'P';
                        $game->user_id_1_correct_answers = 'P';
                    } else {
                        $game->user_id_1_correct_answers = $gameAnswers[$game->user_id_1]
                            ->where('correct', 1)->count();
                        if ($game->solo) {
                            $game->user_id_1_game_points =
                                1 + 0.5 * $game->user_id_1_correct_answers;
                        }
                    }

                    if (!$game->solo) {
                        $game->user_id_2_correct_answers = 0;
                        $game->user_id_2_game_points = 0;
                        if (!isset($gameAnswers[$game->user_id_2])) {
                            $game->user_id_2_game_points = 'F';
                        } elseif ($gameAnswers[$game->user_id_2]->where('corrected', 0)->count()) {
                            $game->user_id_2_game_points = 'P';
                            $game->user_id_2_correct_answers = 'P';
                        } else {
                            $game->user_id_2_correct_answers = $gameAnswers[$game->user_id_2]
                                ->where('correct', 1)->count();
                        }

                        $forfeitScore = [
                            '0' => 0,
                            '1' => 2,
                            '2' => 3,
                            '3' => 5,
                            '4' => 6,
                            '5' => 8,
                            '6' => 9,
                            '7' => 10,
                            '8' => 12
                        ];
                        if (
                            isset($gameAnswers[$game->user_id_1]) &&
                            !isset($gameAnswers[$game->user_id_2])
                        ) {
                            $game->user_id_1_game_points =
                                $forfeitScore[$game->user_id_1_correct_answers];
                        } elseif (
                            !isset($gameAnswers[$game->user_id_1]) &&
                            isset($gameAnswers[$game->user_id_2])
                        ) {
                            $game->user_id_2_game_points =
                                $forfeitScore[$game->user_id_2_correct_answers];
                        } elseif (
                            isset($gameAnswers[$game->user_id_1]) &&
                            isset($gameAnswers[$game->user_id_2])
                        ) {
                            if (
                                $game->user_id_1_game_points === 'P' ||
                                $game->user_id_2_game_points === 'P'
                            ) {
                                $game->user_id_1_game_points = 'P';
                                $game->user_id_2_game_points = 'P';
                            } else {
                                foreach ($gameAnswers[$game->user_id_1] as $key => $value) {
                                    $game->user_id_1_game_points +=
                                        $value['correct'] *
                                        $gameAnswers[$game->user_id_2][$key]['points'];
                                }
                                foreach ($gameAnswers[$game->user_id_2] as $key => $value) {
                                    $game->user_id_2_game_points +=
                                        $value['correct'] *
                                        $gameAnswers[$game->user_id_1][$key]['points'];
                                }
                            }
                        }
                    }
                    if (isset($input['id'])) {
                        $game->answers = $answers
                            ->whereIn('user_id', [$game->user_id_1, $game->user_id_2])
                            ->whereIn('question_id', $questionIds)
                            ->sortBy('question_id')
                            ->groupBy(['user_id', 'question_id'])->map(function ($user) {
                                return $user->map(function ($question) {
                                    return $question->map(function ($answer) {
                                        unset($answer['user_id']);
                                        unset($answer['question_id']);
                                        return $answer;
                                    });
                                });
                            });
                    }
                }
                if ($game->quiz) {
                    if (!isset($input['id'])) {
                        $game->quiz->makeHidden('questions');
                    }
                    if (isset($input['season'])) {
                        $game->makeHidden('quiz');
                    }
                }
                return $game;
            });

            if (isset($input['id'])) {
                $response = $games->first();
            }
            else if (isset($input['season'])) {
                $response = $games->groupBy('round')->map(function($round){
                    return $round->map(function($game){
                        unset($game->season);
                        unset($game->round);
                        return $game;
                    });
                });
            }
            else {
                $response = $games;
            }
            return $this->sendResponse($response, 200);
        }
        
        return $this->sendError('no_permissions', [], 403);
    }
}
