<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Validator;
use App\Http\Controllers\API\BaseController as BaseController;
use App\IndividualQuiz;

class IndividualQuizController extends BaseController
{
    public function list()
    {
        if (Auth::user()->hasPermission('individual_quiz_list')) {
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
            IndividualQuiz::create($input)->save();
            // TODO: save results
            return $this->sendResponse([], 201);
        }

        return $this->sendError('no_permissions', [], 403);
    }
}
