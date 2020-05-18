<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use Request;
use Validator;
use Carbon\Carbon;
use App\Http\Controllers\API\BaseController as BaseController;
use App\IndividualQuiz;
use App\NationalRanking;

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
        } else {
            $response = array_map(function ($ranking) {
                return substr($ranking['date'], 0, -3);
            }, NationalRanking::orderBy('date', 'desc')->get()->toArray());
        }
        return $this->sendResponse($response, 200);
    }

    public function create(Request $request)
    {
        if (Auth::user()->hasPermission('national_ranking_create')) {
            $input = $request::all();
            if (array_key_exists('month', $input)) {
                $input['month'] = $input['month'].'-01';
            }
            $validator = Validator::make($input, [
                'month' => 'required|date_format:Y-m-d|unique:national_rankings,date',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $input['date'] = $input['month'];
            NationalRanking::create($input);
            return $this->sendResponse(null, 201);
        }

        return $this->sendError('no_permissions', [], 403);
    }

    public function delete(Request $request)
    {
        if (Auth::user()->hasPermission('national_ranking_delete')) {
            $input = $request::all();
            if (array_key_exists('month', $input)) {
                $input['month'] = $input['month'].'-01';
            }
            $validator = Validator::make($input, [
                'month' => 'required|exists:national_rankings,date',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            NationalRanking::where('date', $input['month'])->delete();
            return $this->sendResponse();
        }

        return $this->sendError('no_permissions', [], 403);
    }
}
