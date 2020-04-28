<?php

namespace App\Http\Controllers\API;

use Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\IndividualQuiz;
use App\IndividualQuizResult;

class IndividualQuizController extends BaseController
{
    public function get(Request $request)
    {
        $input = $request::all();
        $query = IndividualQuiz::select('id', 'individual_quiz_type', 'date');

        if (array_key_exists('individual_quiz_player_id', $input) && is_array($input['individual_quiz_player_id'])) {
            $individualQuizPlayerQuizzes = IndividualQuizResult::whereIn('individual_quiz_player_id', $input['individual_quiz_player_id'])
                ->get()
                ->pluck('individual_quiz_id')
                ->toArray();
            $query->whereIn('id', $individualQuizPlayerQuizzes);
        } elseif (array_key_exists('individual_quiz_player_id', $input) && !is_array($input['individual_quiz_player_id'])) {
            return $this->sendError('filter_parameters_must_be_arrays', 400);
        }

        $validFilterKeys = ['id', 'individual_quiz_type', 'month'];
        foreach ($input as $key => $value) {
            if (in_array($key, $validFilterKeys) && is_array($value)) {
                if ($key === 'month') {
                    $tableKey = 'date';
                    $ids = array_map(function ($item) {
                        return $item . '-01';
                    }, $value);
                } else {
                    $tableKey = $key;
                    $ids = $value;
                }
                $query->whereIn($tableKey, $ids);
            } elseif (in_array($key, $validFilterKeys) && !is_array($value)) {
                return $this->sendError('filter_parameters_must_be_arrays', 400);
            }
        }

        $individualQuizzes = $query->orderBy('date')->get();

        foreach ($individualQuizzes as $individualQuiz) {
            $individualQuiz->month = substr($individualQuiz->date, 0, -3);
            unset($individualQuiz->date);
            if (array_key_exists('results', $input)) {
                $individualQuiz->results = $individualQuiz->results;
            }
        }

        return $this->sendResponse($individualQuizzes, 200);
    }
}
