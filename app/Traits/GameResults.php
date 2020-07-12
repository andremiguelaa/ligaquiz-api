<?php

namespace App\Traits;

use Carbon\Carbon;
use App\Game;
use App\League;

trait GameResults
{
    public function getGameResults($input, $rules)
    {
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
            $game->done = $game->quiz && $now > $game->quiz->date;
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
        } elseif (isset($input['season'])) {
            $response = $games->groupBy('round')->map(function ($round) {
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
