<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Http\Controllers\API\BaseController as BaseController;
use App\IndividualQuiz;

class NationalRankingController extends BaseController
{
    public function list(Request $request)
    {
        $input = $request->all();
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

            foreach ($individualQuizzes as $individualQuiz) {
                $individualQuiz->results = $individualQuiz->results;
            }

            $response = $individualQuizzes;
            $ranking = array_reduce($individualQuizzes->toArray(), function ($acc, $individualQuiz) {
                foreach ($individualQuiz['results'] as $result) {
                    if (!array_key_exists($result['individual_quiz_player_id'], $acc)) {
                        $acc[$result['individual_quiz_player_id']] = (object) [
                            'individual_quiz_player_id' => $result['individual_quiz_player_id'],
                            'rank' => null,
                            'score' => 0,
                            'months' => []
                        ];
                    }
                    $acc[$result['individual_quiz_player_id']]->score += $result['score'];
                    $month = substr($individualQuiz['date'], 0, -3);
                    if (!array_key_exists($month, $acc[$result['individual_quiz_player_id']]->months)) {
                        $acc[$result['individual_quiz_player_id']]->months[$month] = [];
                    }
                    array_push($acc[$result['individual_quiz_player_id']]->months[$month], $result);
                }
                return $acc;
            }, []);

            usort($ranking, function ($a, $b) {
                return strcmp($b->score, $a->score);
            });

            // TODO: calculate rank for each player

            $response = array_values($ranking);
        } else {
            $response = array_map(function ($individualQuiz) {
                return substr($individualQuiz['date'], 0, -3);
            }, IndividualQuiz::select('date')->distinct()->get()->toArray());
        }
        return $this->sendResponse($response, 200);
    }
}
