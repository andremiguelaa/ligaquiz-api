<?php

namespace App\Traits;

use Carbon\Carbon;
use App\League;
use App\Round;
use App\Quiz;
use App\Answer;
use App\Media;

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
            ->where('submitted', 1)
            ->select('user_id', 'question_id', 'points', 'correct', 'corrected')
            ->get()
            ->groupBy(['question_id', 'user_id']);

        $games = $games->map(function ($game) use ($games, $answers) {
            $now = Carbon::now()->format('Y-m-d');
            $game->done = $game->round->date && $now > $game->round->date;
            $game->corrected = true;
            $game->solo = $game->user_id_1 === $game->user_id_2;
            if ($game->quiz && $now > $game->round->date) {
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

                $questionIds = $game->quiz->questions->pluck('question_id')->toArray();
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
                } else {
                    if ($game->solo) {
                        $game->user_id_1_game_points =
                            1 + 0.5 * $game->user_id_1_correct_answers;
                    }
                }

                if (!$game->solo) {
                    if (!$game->user_id_2_submitted_answers) {
                        $game->user_id_2_game_points = 'F';
                    } elseif ($game->user_id_2_corrected_answers < 8) {
                        $game->user_id_2_game_points = 'P';
                        $game->user_id_2_correct_answers = 'P';
                        $game->corrected = false;
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
                        if (
                            $game->user_id_1_game_points === 'P' ||
                            $game->user_id_2_game_points === 'P'
                        ) {
                            $game->user_id_1_game_points = 'P';
                            $game->user_id_2_game_points = 'P';
                        } else {
                            foreach ($questionIds as $questionId) {
                                $game->user_id_1_game_points +=
                                    $answers[$questionId][$game->user_id_1]->first()->correct *
                                        $answers[$questionId][$game->user_id_2]->first()->points;
                                $game->user_id_2_game_points +=
                                    $answers[$questionId][$game->user_id_2]->first()->correct *
                                        $answers[$questionId][$game->user_id_1]->first()->points;
                            }
                        }
                    }
                }
                if ($games->count() === 1) {
                    $mappedAnswers = $answers->map(function ($question) {
                        return $question->map(function ($user) {
                            $mappedUser = $user->first();
                            unset($mappedUser->user_id);
                            unset($mappedUser->question_id);
                            return $mappedUser;
                        });
                    });
                    $game->answers = $mappedAnswers;
                }
            }
            if ($game->quiz) {
                if ($games->count() > 1) {
                    $game->quiz->makeHidden('questions');
                } else {
                    $mediaIds = $game->quiz->questions->map(function ($question) {
                        return $question->question->media_id;
                    })->toArray();
                    $game->media = array_reduce(
                        Media::whereIn('id', $mediaIds)->get()->toArray(),
                        function ($carry, $item) {
                            $mediaFile = $item;
                            unset($mediaFile['id']);
                            $carry[$item['id']] = $mediaFile;
                            return $carry;
                        },
                        []
                    );
                }
            }
            if ($games->count() > 1) {
                $game->makeHidden('quiz');
            }
            $game->makeHidden('round_id');
            return $game;
        });

        if ($games->count() === 1) {
            $game = $games->first();
            if ($game->quiz) {
                unset($game->quiz->questions);
                $game->quiz->questions = Quiz::with('questions.question')
                    ->find($game->quiz->id)
                    ->questions
                    ->map(function ($item) use($game) {
                        $question = $item->question;
                        $question->percentage =
                            $game->answers[$item->question_id]->where('correct', 1)->count() /
                            $game->answers[$item->question_id]->count() *
                            100;
                        return $question;
                    });
            }
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
