<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Validator;
use App\Http\Controllers\API\BaseController as BaseController;
use App\IndividualQuiz;
use App\IndividualQuizResult;

class IndividualQuizController extends BaseController
{
    public function list(Request $request)
    {
        if (Auth::user()->hasPermission('individual_quiz_list')) {
            $input = $request->all();
            if (array_key_exists('id', $input)) {
                if (!is_array($input['id'])) {
                    return $this->sendError('id_must_be_array', 400);
                }
                $individualQuizzes = IndividualQuiz::whereIn('id', $input['id'])
                    ->select('id', 'individual_quiz_type', 'date')
                    ->get();
                if ($individualQuizzes->count() != count($input['id'])) {
                    return $this->sendError('quizzes_not_found', 404);
                }
                if (array_key_exists('results', $input)) {
                    foreach ($individualQuizzes as $individualQuiz) {
                        $individualQuiz->results = IndividualQuizResult::where('individual_quiz_id', $individualQuiz->id)
                            ->select('individual_quiz_player_id', 'result')
                            ->get();
                    }
                }
                return $this->sendResponse($individualQuizzes, 200);
            }
            return $this->sendResponse(IndividualQuiz::all(), 200);
        }

        return $this->sendError('no_permissions', [], 403);
    }

    public function create(Request $request)
    {
        if (Auth::user()->hasPermission('individual_quiz_create')) {
            $input = $request->all();
            $validator = Validator::make($input, [
                'individual_quiz_type' => 'required|exists:individual_quiz_types,slug|unique_with:individual_quizzes,date',
                'date' => 'required|date_format:Y-m-d',
                'results' => 'required|array',
            ]);
            $validResults = true;
            foreach ($input['results'] as $result) {
                $resultValidator = Validator::make($result, [
                    'individual_quiz_player_id' => 'required|exists:individual_quiz_players,id',
                    'result' => 'required|integer',
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
            $individualQuiz = IndividualQuiz::create($input);

            foreach ($input['results'] as $result) {
                $result['individual_quiz_id'] = $individualQuiz->id;
                IndividualQuizResult::create($result);
            }
            return $this->sendResponse([], 201);
        }

        return $this->sendError('no_permissions', [], 403);
    }

    public function update(Request $request)
    {
        if (Auth::user()->hasPermission('individual_quiz_edit')) {
            $input = $request->all();
            $validator = Validator::make($input, [
                'id' => 'required|exists:individual_quizzes,id',
                'individual_quiz_type' => [
                    'required',
                    'exists:individual_quiz_types,slug',
                    'unique_with:individual_quizzes,date,' . $input['id'],
                ],
                'date' => 'required|date_format:Y-m-d',
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
            return $this->sendResponse([], 201);
        }

        return $this->sendError('no_permissions', [], 403);
    }
}
