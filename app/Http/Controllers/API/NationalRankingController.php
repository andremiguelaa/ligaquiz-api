<?php

namespace App\Http\Controllers\API;

use Request;
use Carbon\Carbon;
use App\Http\Controllers\API\BaseController as BaseController;
use App\IndividualQuiz;

class NationalRankingController extends BaseController
{
    public function get(Request $request)
    {
        $input = $request::all();
        if (array_key_exists('month', $input)) {
            $endDate = Carbon::createFromFormat('Y-m-d', $input['month'] . '-01');
            $startDate = (clone $endDate)->subMonths(11);

            $individualQuizzes = IndividualQuiz::whereBetween(
                'date',
                [
                    $startDate->format('Y-m-d'),
                    $endDate->format('Y-m-d')
                ]
            )
            ->select('id', 'date', 'individual_quiz_type')
            ->orderBy('date', 'asc')
            ->get();

            if (!count($individualQuizzes)) {
                return $this->sendError('not_found', null, 404);
            }

            foreach ($individualQuizzes as $individualQuiz) {
                $individualQuiz->results = $individualQuiz->results;
            }

            $rankingPlayers = array_reduce($individualQuizzes->toArray(), function ($acc, $individualQuiz) {
                foreach ($individualQuiz['results'] as $result) {
                    $playerId = $result['individual_quiz_player_id'];
                    $quizType = $individualQuiz['individual_quiz_type'];
                    if (!array_key_exists($playerId, $acc)) {
                        $acc[$playerId] = (object) [
                            'individual_quiz_player_id' => $playerId,
                            'rank' => null,
                            'sum' => 0,
                            'quizzes' => [],
                            'quiz_count' => 0,
                            'resultsByQuizType' => []
                        ];
                    }
                    $acc[$playerId]->sum += $result['score'];
                    $month = substr($individualQuiz['date'], 0, -3);
                    if (!array_key_exists($quizType, $acc[$playerId]->quizzes)) {
                        $acc[$playerId]->quizzes[$quizType] = (object) [];
                    }
                    $result['individual_quiz_id'] = $individualQuiz['id'];
                    unset($result['individual_quiz_player_id']);
                    $acc[$playerId]->quizzes[$quizType]->{$month} = $result;
                    if (!array_key_exists($quizType, $acc[$playerId]->resultsByQuizType)) {
                        $acc[$playerId]->resultsByQuizType[$quizType] = [];
                    }
                    array_push($acc[$playerId]->resultsByQuizType[$quizType], $result['score']);
                    $acc[$playerId]->quiz_count++;
                }
                return $acc;
            }, []);

            foreach ($rankingPlayers as $player) {
                $validResults = array_reduce($player->resultsByQuizType, function ($acc, $results) {
                    arsort($results);
                    $bestFive = array_slice($results, 0, 5);
                    $acc = array_merge($acc, $bestFive);
                    return $acc;
                }, []);
                arsort($validResults);
                $bestTen = array_slice($validResults, 0, 10);
                $player->score = array_sum($bestTen);
                unset($player->resultsByQuizType);
                $player->average = $player->sum/$player->quiz_count;
            }

            usort($rankingPlayers, function ($a, $b) {
                return $b->score > $a->score;
            });

            $rank = 1;
            foreach ($rankingPlayers as $key => $player) {
                if (!($key > 0 && $rankingPlayers[$key - 1]->score === $rankingPlayers[$key]->score)) {
                    $rank = $key + 1;
                }
                $player->rank = $rank;
            }

            $ranking = array_values($rankingPlayers);

            $quizzes = array_reduce($individualQuizzes->toArray(), function ($acc, $individualQuiz) {
                $month = substr($individualQuiz['date'], 0, -3);
                if (!array_key_exists($individualQuiz['individual_quiz_type'], $acc)) {
                    $acc[$individualQuiz['individual_quiz_type']] = [];
                }
                array_push($acc[$individualQuiz['individual_quiz_type']], $month);
                return $acc;
            }, []);

            $response = (object) [
                'ranking' => $ranking,
                'quizzes' => $quizzes
            ];
        } elseif (array_key_exists('complete', $input)) {
            $all = array_map(function ($individualQuiz) {
                return substr($individualQuiz['date'], 0, -3);
            }, IndividualQuiz::select('date')->distinct()->orderBy('date', 'desc')->get()->toArray());
            $response = array_reduce($all, function ($acc, $month) use ($all) {
                $yearAgo = Carbon::createFromFormat('Y-m', $month)->subMonths(11)->format('Y-m');
                if (in_array($yearAgo, $all)) {
                    array_push($acc, $month);
                }
                return $acc;
            }, []);
        } else {
            $response = array_map(function ($individualQuiz) {
                return substr($individualQuiz['date'], 0, -3);
            }, IndividualQuiz::select('date')->distinct()->orderBy('date', 'desc')->get()->toArray());
        }
        return $this->sendResponse($response, 200);
    }
}
