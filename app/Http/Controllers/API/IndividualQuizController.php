<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use Request;
use Validator;
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

    public function create(Request $request)
    {
        if (Auth::user()->hasPermission('individual_quiz_create')) {
            $input = $request::all();
            $input['date'] = null;
            if (array_key_exists('month', $input)) {
                $input['date'] = $input['month'] . '-01';
            }
            $validator = Validator::make($input, [
                'individual_quiz_type' => 'required|exists:individual_quiz_types,slug|unique_with:individual_quizzes,date',
                'month' => 'required|date_format:Y-m',
                'results' => 'required|array',
            ]);
            $validResults = true;
            if (array_key_exists('results', $input)) {
                foreach ($input['results'] as $result) {
                    $resultValidator = Validator::make($result, [
                        'individual_quiz_player_id' => 'required|exists:individual_quiz_players,id',
                        'result' => 'required|integer',
                    ]);
                    if ($resultValidator->fails()) {
                        $validResults = false;
                    }
                }
            }
            if ($validator->fails() || !$validResults) {
                if (!$validResults) {
                    $validator->errors()->add('results', 'validation.results');
                }
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $individualQuiz = IndividualQuiz::create($input);

            foreach ($input['results'] as $result) {
                $result['individual_quiz_id'] = $individualQuiz->id;
                IndividualQuizResult::create($result);
            }
            return $this->sendResponse(null, 201);
        }

        return $this->sendError('no_permissions', [], 403);
    }

    public function update(Request $request)
    {
        if (Auth::user()->hasPermission('individual_quiz_edit')) {
            $input = $request::all();
            $input['date'] = null;
            if (array_key_exists('month', $input)) {
                $input['date'] = $input['month'] . '-01';
            }
            $validator = Validator::make($input, [
                'id' => 'required|exists:individual_quizzes,id',
                'individual_quiz_type' => [
                    'required',
                    'exists:individual_quiz_types,slug',
                    array_key_exists('id', $input) ? 'unique_with:individual_quizzes,date,' . $input['id'] : '',
                ],
                'month' => 'required|date_format:Y-m',
                'results' => 'required|array',
            ]);
            $validResults = true;
            foreach ($input['results'] as $result) {
                $resultValidator = Validator::make($result, [
                    'individual_quiz_player_id' => 'required|exists:individual_quiz_players,id',
                    'result' => 'integer',
                ]);
                if ($resultValidator->fails()) {
                    $validResults = false;
                }
            }
            if ($validator->fails() || !$validResults) {
                if (!$validResults) {
                    $validator->errors()->add('results', 'validation.results');
                }
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $individualQuiz = IndividualQuiz::find($input['id']);
            $individualQuiz->fill($input)->save();

            foreach ($input['results'] as $result) {
                $savedResult = IndividualQuizResult::where('individual_quiz_player_id', $result['individual_quiz_player_id'])->first();
                if ($savedResult) {
                    if (array_key_exists('result', $result)) {
                        $savedResult->result = $result['result'];
                        $savedResult->save();
                    } else {
                        $savedResult->delete();
                    }
                } elseif (array_key_exists('result', $result)) {
                    $result['individual_quiz_id'] = $input['id'];
                    IndividualQuizResult::create($result);
                }
            }
            return $this->sendResponse(null, 201);
        }

        return $this->sendError('no_permissions', [], 403);
    }

    public function delete(Request $request)
    {
        if (Auth::user()->hasPermission('individual_quiz_delete')) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'id' => 'required|exists:individual_quizzes,id',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            IndividualQuiz::find($input['id'])->delete();
            IndividualQuizResult::where('individual_quiz_id', $input['id'])->delete();
            return $this->sendResponse();
        }

        return $this->sendError('no_permissions', [], 403);
    }
}
