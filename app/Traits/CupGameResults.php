<?php

namespace App\Traits;

use Carbon\Carbon;
use App\Round;
use App\Quiz;
use App\Answer;

trait CupGameResults
{
    public function getRoundsResults($cup)
    {
        $rounds = $cup->rounds;
        $roundIds = $rounds->pluck('round_id')->toArray();
        $dates = Round::whereIn('id', $roundIds)->get()->pluck('date')->toArray();
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

        foreach ($rounds as $key => $round) {
            $round->date = $dates[$key];
            $round->games->map(function ($game) use ($cup, $key, $dates, $quizzesByDate, $answers) {
                $now = Carbon::now()->format('Y-m-d');
                $roundDate = $dates[$key];
                $game->done = $roundDate && $now > $roundDate;
                $game->corrected = true;
                $game->solo = !boolval($game->user_id_2);
                if (isset($quizzesByDate[$roundDate]) && $now > $roundDate) {
                    $game->user_id_1_game_points = 0;
                    $game->user_id_1_submitted_answers = 0;
                    $game->user_id_1_corrected_answers = 0;
                    $game->user_id_1_correct_answers = 0;
                    if (!$game->solo) {
                        $game->user_id_2_game_points = 0;
                        $game->user_id_2_submitted_answers = 0;
                        $game->user_id_2_corrected_answers = 0;
                        $game->user_id_2_correct_answers = 0;
                    }
                    $questionIds = $quizzesByDate[$roundDate][0]->questions->pluck('question_id')->toArray();
                    foreach ($questionIds as $questionId) {
                        if (isset($answers[$questionId][$game->user_id_1])) {
                            $game->user_id_1_submitted_answers++;
                            if ($answers[$questionId][$game->user_id_1]->first()->corrected) {
                                $game->user_id_1_corrected_answers++;
                            }
                            if ($answers[$questionId][$game->user_id_1]->first()->correct) {
                                $game->user_id_1_correct_answers++;
                            }
                        }
                        if (!$game->solo && isset($answers[$questionId][$game->user_id_2])) {
                            $game->user_id_2_submitted_answers++;
                            if ($answers[$questionId][$game->user_id_2]->first()->corrected) {
                                $game->user_id_2_corrected_answers++;
                            }
                            if ($answers[$questionId][$game->user_id_2]->first()->correct) {
                                $game->user_id_2_correct_answers++;
                            }
                        }
                    }
                    if (!$game->user_id_1_submitted_answers) {
                        $game->user_id_1_game_points = 'F';
                    } elseif ($game->user_id_1_corrected_answers < 8) {
                        $game->user_id_1_game_points = 'P';
                        $game->user_id_1_correct_answers = 'P';
                        $game->corrected = false;
                    }
                    if (!$game->solo) {
                        if (!$game->user_id_2_submitted_answers) {
                            $game->user_id_2_game_points = 'F';
                        } elseif ($game->user_id_2_corrected_answers < 8) {
                            $game->user_id_2_game_points = 'P';
                            $game->user_id_2_correct_answers = 'P';
                            $game->corrected = false;
                        }
                        if (
                            !(
                                $game->user_id_1_correct_answers === 'P' ||
                                $game->user_id_2_correct_answers === 'P'
                            )
                        ) {
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
                                $game->user_id_1_submitted_answers &&
                                !$game->user_id_2_submitted_answers
                            ) {
                                $game->user_id_1_game_points =
                                    $forfeitScore[$game->user_id_1_correct_answers];
                            } elseif (
                                !$game->user_id_1_submitted_answers &&
                                $game->user_id_2_submitted_answers
                            ) {
                                $game->user_id_2_game_points =
                                    $forfeitScore[$game->user_id_2_correct_answers];
                            } elseif (
                                $game->user_id_1_submitted_answers &&
                                $game->user_id_2_submitted_answers
                            ) {
                                foreach ($questionIds as $questionId) {
                                    $game->user_id_1_game_points +=
                                        $answers[$questionId][$game->user_id_1]->first()->correct *
                                            $answers[$questionId][$game->user_id_2]->first()->cup_points;
                                    $game->user_id_2_game_points +=
                                        $answers[$questionId][$game->user_id_2]->first()->correct *
                                            $answers[$questionId][$game->user_id_1]->first()->cup_points;
                                }
                            }
                        }
                    }
                }
                if ($game->solo) {
                    $game->winner = $game->user_id_1;
                } elseif (
                    is_numeric($game->user_id_1_game_points) &&
                    is_numeric($game->user_id_2_game_points)
                ) {
                    if ($game->user_id_1_game_points > $game->user_id_2_game_points) {
                        $game->winner = $game->user_id_1;
                    } elseif ($game->user_id_1_game_points < $game->user_id_2_game_points) {
                        $game->winner = $game->user_id_2;
                    }
                } elseif (
                    is_numeric($game->user_id_1_game_points) &&
                    $game->user_id_2_game_points !== 'P'
                ) {
                    $game->winner = $game->user_id_1;
                } elseif (
                    is_numeric($game->user_id_2_game_points) &&
                    $game->user_id_1_game_points !== 'P'
                ) {
                    $game->winner = $game->user_id_2;
                }
                if (!isset($game->winner)) {
                    if (
                        isset($cup->tiebreakers[$game->user_id_1]) &&
                        isset($cup->tiebreakers[$game->user_id_1]['current_tier']) &&
                        isset($cup->tiebreakers[$game->user_id_1]['last_tier']) &&
                        isset($cup->tiebreakers[$game->user_id_2]) &&
                        isset($cup->tiebreakers[$game->user_id_2]['current_tier']) &&
                        isset($cup->tiebreakers[$game->user_id_2]['last_tier'])
                    ) {
                        if ($cup->tiebreakers[$game->user_id_1]['current_tier'] > $cup->tiebreakers[$game->user_id_2]['current_tier']) {
                            $game->winner = $game->user_id_1;
                        } elseif ($cup->tiebreakers[$game->user_id_1]['current_tier'] < $cup->tiebreakers[$game->user_id_2]['current_tier']) {
                            $game->winner = $game->user_id_2;
                        }
                        if (!isset($game->winner)) {
                            if ($cup->tiebreakers[$game->user_id_1]['last_tier'] > $cup->tiebreakers[$game->user_id_2]['last_tier']) {
                                $game->winner = $game->user_id_1;
                            } elseif ($cup->tiebreakers[$game->user_id_1]['last_tier'] < $cup->tiebreakers[$game->user_id_2]['last_tier']) {
                                $game->winner = $game->user_id_2;
                            }
                            if (!isset($game->winner)) {
                                if ($cup->tiebreakers[$game->user_id_1]['last_rank'] > $cup->tiebreakers[$game->user_id_2]['last_rank']) {
                                    $game->winner = $game->user_id_1;
                                } elseif ($cup->tiebreakers[$game->user_id_1]['last_rank'] < $cup->tiebreakers[$game->user_id_2]['last_rank']) {
                                    $game->winner = $game->user_id_2;
                                }
                            }
                        }
                    }
                    if (!isset($game->winner)) {
                        if ($game->user_id_1 > $game->user_id_2) {
                            $game->winner = $game->user_id_1;
                        } else {
                            $game->winner = $game->user_id_2;
                        }
                    }
                }
                if ($game->corrected) {
                    unset($game->winner);
                }
                unset($game->solo);
                unset($game->cup_round_id);
                return $game;
            });
        }
        return $rounds;
    }
}
