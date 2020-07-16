<?php

namespace App\Traits;

use Carbon\Carbon;
use App\Game;
use App\League;
use App\Round;
use App\Quiz;
use App\Answer;

trait GameResults
{
    public function getGameResults($input, $rules)
    {
        $query = Game::with('quiz');
        foreach ($input as $key => $value) {
            if (in_array($key, array_keys($rules))) {
                if ($key === 'user' || $key === 'opponent') {
                    $query->where(function ($userQuery) use ($value) {
                        $userQuery->where('user_id_1', $value)->orWhere('user_id_2', $value);
                    });
                } elseif ($key === 'tier') {
                    $users = League::where('season_id', $input['season_id'])
                        ->where('tier', $input['tier'])
                        ->first()
                        ->user_ids;
                    $query->whereIn('user_id_1', $users)->whereIn('user_id_2', $users);
                } elseif ($key === 'season_id') {
                    $roundIds = Round::where('season_id', $value)->get()->pluck('id')->toArray();
                    $query->whereIn('round_id', $roundIds);
                } else {
                    $query->where($key, $value);
                }
            }
        }
        $games = $query->get();
        $dates = $games->pluck('quiz.date')->unique()->toArray();
        if (isset($input['id'])) {
            $quizzes = Quiz::with('questions.question')->whereIn('date', $dates)->get();
        } else {
            $quizzes = Quiz::with('questions')->whereIn('date', $dates)->get();
        }
        $questionIds = [];
        foreach ($quizzes as $quiz) {
            $questionIds = array_merge(
                $questionIds,
                $quiz->questions->pluck('question_id')->toArray()
            );
        }
        $playerIds = [];
        foreach ($games as $game) {
            array_push($playerIds, $game->user_id_1);
            array_push($playerIds, $game->user_id_2);
        }
        $answers = Answer::whereIn('question_id', $questionIds)
            ->whereIn('user_id', array_unique($playerIds))
            ->where('submitted', 1)
            ->select('user_id', 'question_id', 'points', 'correct', 'corrected')
            ->get();
        $games = $games->map(function ($game) use ($input, $answers) {
            $now = Carbon::now()->format('Y-m-d');
            $game->done = $game->round->date && $now > $game->round->date;
            if ($game->quiz && $now > $game->round->date) {
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
                    $game->answers = $gameAnswers;
                }
            }
            if ($game->quiz) {
                if (!isset($input['id'])) {
                    $game->quiz->makeHidden('questions');
                }
                if (isset($input['season_id'])) {
                    $game->makeHidden('quiz');
                }
            }
            $game->makeHidden('round_id');
            return $game;
        });

        if (isset($input['id'])) {
            $game = $games->first();
            unset($game->quiz->questions);
            $game->quiz->questions = Quiz::with('questions.question')->find($game->id)->questions;
            unset($game->round);
            $response = $game;
        } elseif (isset($input['season_id'])) {
            $response = $games->groupBy(function ($game) {
                return $game->round->round;
            })->map(function ($round) {
                return $round->map(function ($game) {
                    unset($game->season);
                    unset($game->round);
                    return $game;
                });
            });
        } else {
            $response = $games;
        }

        return $response;
    }
}
