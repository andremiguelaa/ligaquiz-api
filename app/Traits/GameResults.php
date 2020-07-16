<?php

namespace App\Traits;

use Carbon\Carbon;
use App\League;
use App\Round;
use App\Quiz;
use App\Answer;

trait GameResults
{
    public function getGameResults($games, $tier)
    {
        $dates = $games->pluck('quiz.date')->unique()->toArray();
        if ($games->count() === 1) {
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
        $games = $games->map(function ($game) use ($games, $answers) {
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
                if ($games->count() === 1) {
                    $game->answers = $gameAnswers;
                }
            }
            if ($game->quiz) {
                if ($games->count() > 1) {
                    $game->quiz->makeHidden('questions');
                    $game->makeHidden('quiz');
                }
            }
            $game->makeHidden('round_id');
            return $game;
        });

        if ($games->count() === 1) {
            $game = $games->first();
            unset($game->quiz->questions);
            $game->quiz->questions = Quiz::with('questions.question')->find($game->id)->questions;
            unset($game->round);
            $response = $game;
        } elseif ($tier) {
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
