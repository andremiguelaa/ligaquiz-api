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
            )->select('id', 'date', 'individual_quiz_type')->get();

            foreach ($individualQuizzes as $individualQuiz) {
                $individualQuiz->results = $individualQuiz->results;
            }

            // TODO: calculate ranking for month
            $response = $individualQuizzes;
        } else {
            $response = array_map(function ($value) {
                return substr($value['date'], 0, -3);
            }, IndividualQuiz::select('date')->distinct()->get()->toArray());
        }
        return $this->sendResponse($response, 200);
    }
}
