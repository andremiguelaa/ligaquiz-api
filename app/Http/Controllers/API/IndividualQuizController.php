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

        if (
            array_key_exists('individual_quiz_player_id', $input) &&
            is_array($input['individual_quiz_player_id'])
        ) {
            $individualQuizPlayerQuizzes = IndividualQuizResult::whereIn(
                    'individual_quiz_player_id',
                    $input['individual_quiz_player_id']
                )
                ->get()
                ->pluck('individual_quiz_id')
                ->toArray();
            $query->whereIn('id', $individualQuizPlayerQuizzes);
        } elseif (
            array_key_exists('individual_quiz_player_id', $input) &&
            !is_array($input['individual_quiz_player_id'])
        ) {
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

        $individualQuizzes = $query->orderBy('date', 'desc')->get();

        foreach ($individualQuizzes as $individualQuiz) {
            $individualQuiz->month = substr($individualQuiz->date, 0, -3);
            if (array_key_exists('results', $input)) {
                $individualQuiz->results = $individualQuiz->results;
            }
            unset($individualQuiz->id);
            unset($individualQuiz->date);
        }

        return $this->sendResponse($individualQuizzes, 200);
    }

    public function create(Request $request)
    {
        if (Auth::user()->hasPermission('national_ranking_create')) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'month' => 'required|date_format:Y-m',
                'individual_quizzes' => 'required|array',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            foreach ($input['individual_quizzes'] as $individualQuiz) {
                $individualQuiz['date'] = $input['month'] . '-01';
                $individualQuizValidator = Validator::make($individualQuiz, [
                    'individual_quiz_type' => 'required|exists:individual_quiz_types,slug|unique_with:individual_quizzes,date',
                    'results' => 'required|array',
                ]);
                if ($individualQuizValidator->fails()) {
                    return $this->sendError('validation_error', [
                        'individual_quizzes' => 'validation.format'
                    ], 400);
                }
                foreach ($individualQuiz['results'] as $result) {
                    $resultValidator = Validator::make($result, [
                        'individual_quiz_player_id' => 'required|exists:individual_quiz_players,id',
                        'result' => 'required|integer',
                    ]);
                    if ($resultValidator->fails()) {
                        return $this->sendError('validation_error', [
                            'individual_quizzes' => 'validation.format'
                        ], 400);
                    }
                }
            }

            foreach ($input['individual_quizzes'] as $individualQuiz) {
                $individualQuiz['date'] = $input['month'] . '-01';
                $newIndividualQuiz = IndividualQuiz::create($individualQuiz);
                foreach ($individualQuiz['results'] as $result) {
                    $result['individual_quiz_id'] = $newIndividualQuiz->id;
                    IndividualQuizResult::create($result);
                }
            }

            $individualQuizzes = IndividualQuiz::select('id', 'individual_quiz_type', 'date')
                ->where('date', $input['month'] . '-01')->get();
            foreach ($individualQuizzes as $individualQuiz) {
                $individualQuiz->results = $individualQuiz->results;
                unset($individualQuiz->id);
                unset($individualQuiz->date);
            }
            return $this->sendResponse($individualQuizzes, 201);
        }

        return $this->sendError('no_permissions', [], 403);
    }

    public function update(Request $request)
    {
        if (Auth::user()->hasPermission('national_ranking_edit')) {
            $input = $request::all();
            $input['date'] = null;
            if (array_key_exists('month', $input)) {
                $input['date'] = $input['month'] . '-01';
            }
            $validator = Validator::make($input, [
                'month' => 'required|date_format:Y-m',
                'individual_quizzes' => 'required|array',
                'date' => 'exists:individual_quizzes,date'
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            foreach ($input['individual_quizzes'] as $individualQuiz) {
                $individualQuiz['date'] = $input['month'] . '-01';
                $individualQuizValidator = Validator::make($individualQuiz, [
                    'individual_quiz_type' => 'required|exists:individual_quiz_types,slug',
                    'results' => 'required|array',
                ]);
                if ($individualQuizValidator->fails()) {
                    return $this->sendError('validation_error', [
                        'individual_quizzes' => 'validation.format'
                    ], 400);
                }
                foreach ($individualQuiz['results'] as $result) {
                    $resultValidator = Validator::make($result, [
                        'individual_quiz_player_id' => 'required|exists:individual_quiz_players,id',
                        'result' => 'required|integer',
                    ]);
                    if ($resultValidator->fails()) {
                        return $this->sendError('validation_error', [
                            'individual_quizzes' => 'validation.format'
                        ], 400);
                    }
                }
            }

            $oldIndividualQuizzes = IndividualQuiz::where('date', $input['month'].'-01')->get();
            foreach ($oldIndividualQuizzes as $oldIndividualQuiz) {
                IndividualQuizResult::where('individual_quiz_id', $oldIndividualQuiz->id)->delete();
                $oldIndividualQuiz->delete();
            }
            foreach ($input['individual_quizzes'] as $individualQuiz) {
                $individualQuiz['date'] = $input['month'] . '-01';
                $newIndividualQuiz = IndividualQuiz::create($individualQuiz);
                foreach ($individualQuiz['results'] as $result) {
                    $result['individual_quiz_id'] = $newIndividualQuiz->id;
                    IndividualQuizResult::create($result);
                }
            }

            $individualQuizzes = IndividualQuiz::select('id', 'individual_quiz_type', 'date')
                ->where('date', $input['month'] . '-01')->get();
            foreach ($individualQuizzes as $individualQuiz) {
                $individualQuiz->results = $individualQuiz->results;
                unset($individualQuiz->id);
                unset($individualQuiz->date);
            }
            return $this->sendResponse($individualQuizzes, 201);
        }
        return $this->sendError('no_permissions', [], 403);
    }

    public function delete(Request $request)
    {
        if (Auth::user()->hasPermission('individual_quiz_delete')) {
            $input = $request::all();
            if (array_key_exists('month', $input)) {
                $input['month'] = $input['month'].'-01';
            }
            $validator = Validator::make($input, [
                'month' => 'required|exists:individual_quizzes,date',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $individualQuizzes = IndividualQuiz::where('date', $input['month'])->get();
            foreach ($individualQuizzes as $individualQuiz) {
                IndividualQuizResult::where('individual_quiz_id', $individualQuiz->id)->delete();
            }
            IndividualQuiz::where('date', $input['month'])->delete();
            return $this->sendResponse();
        }

        return $this->sendError('no_permissions', [], 403);
    }
}
