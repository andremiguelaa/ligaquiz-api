<?php

namespace App\Traits;

use Carbon\Carbon;
use App\Round;
use App\Quiz;
use App\Answer;

trait Cup
{
    public function getCupGameResults($games, $quizzesByDate, $answers)
    {
        return $games->map(function ($game) use ($quizzesByDate, $answers) {
            $now = Carbon::now()->format('Y-m-d');
            $roundDate = $game->cupRound->round->date;
            $game->done = $roundDate && $now > $roundDate;
            if (isset($quizzesByDate[$roundDate]) && $game->done) {
                $solo = !boolval($game->user_id_2);
                $game->corrected = true;
                $game->user_id_1_game_points = 0;
                $game->user_id_1_submitted_answers = 0;
                $game->user_id_1_corrected_answers = 0;
                $game->user_id_1_correct_answers = 0;
                if (!$solo) {
                    $game->user_id_2_game_points = 0;
                    $game->user_id_2_submitted_answers = 0;
                    $game->user_id_2_corrected_answers = 0;
                    $game->user_id_2_correct_answers = 0;
                }
                $questionIds = $quizzesByDate[$roundDate][0]
                    ->questions
                    ->pluck('question_id')
                    ->toArray();
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
                    if (!$solo && isset($answers[$questionId][$game->user_id_2])) {
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
                if (!$solo) {
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
                if ($solo) {
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
                    $cup = $game->cupRound->cup;
                    if (
                        isset($cup->tiebreakers[$game->user_id_1]) &&
                        isset($cup->tiebreakers[$game->user_id_1]['current_tier']) &&
                        isset($cup->tiebreakers[$game->user_id_1]['last_tier']) &&
                        isset($cup->tiebreakers[$game->user_id_2]) &&
                        isset($cup->tiebreakers[$game->user_id_2]['current_tier']) &&
                        isset($cup->tiebreakers[$game->user_id_2]['last_tier'])
                    ) {
                        if (
                            $cup->tiebreakers[$game->user_id_1]['current_tier'] >
                            $cup->tiebreakers[$game->user_id_2]['current_tier']
                        ) {
                            $game->winner = $game->user_id_1;
                        } elseif (
                            $cup->tiebreakers[$game->user_id_1]['current_tier'] <
                            $cup->tiebreakers[$game->user_id_2]['current_tier']
                        ) {
                            $game->winner = $game->user_id_2;
                        }
                        if (!isset($game->winner)) {
                            if (
                                $cup->tiebreakers[$game->user_id_1]['last_tier'] >
                                $cup->tiebreakers[$game->user_id_2]['last_tier']
                            ) {
                                $game->winner = $game->user_id_1;
                            } elseif (
                                $cup->tiebreakers[$game->user_id_1]['last_tier'] <
                                $cup->tiebreakers[$game->user_id_2]['last_tier']
                            ) {
                                $game->winner = $game->user_id_2;
                            }
                            if (!isset($game->winner)) {
                                if (
                                    $cup->tiebreakers[$game->user_id_1]['last_rank'] >
                                    $cup->tiebreakers[$game->user_id_2]['last_rank']
                                ) {
                                    $game->winner = $game->user_id_1;
                                } elseif (
                                    $cup->tiebreakers[$game->user_id_1]['last_rank'] <
                                    $cup->tiebreakers[$game->user_id_2]['last_rank']
                                ) {
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
            }
            unset($game->cup);
            unset($game->cup_round_id);
            return $game;
        });
    }

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
            $roundDate = $round->date;
            $round->games = $this->getCupGameResults(
                $round->games,
                $quizzesByDate,
                $answers
            );
        }
        return $rounds;
    }
}
