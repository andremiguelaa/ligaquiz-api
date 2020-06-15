<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use Request;
use Validator;
use Carbon\Carbon;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Answer;

class AnswerController extends BaseController
{
    public function create(Request $request)
    {
        $input = $request::all();
        $validator = Validator::make($input, [
            'question_id' => 'exists:questions,id',
            'text' => 'string',
            'points' => 'integer|min:0|max:3',
        ]);
        if ($validator->fails()) {
            return $this->sendError('validation_error', $validator->errors(), 400);
        }
        $answer = Answer::create([
            'question_id' => intval($input['question_id']),
            'user_id' => Auth::id(),
            'text' => $input['text'],
            'points' => isset($input['points']) ? $input['points'] : -1,
            'correct' => 0,
            'corrected' => 0,
            'submitted' => 0,
        ]);
        return $this->sendResponse($answer, 201);
    }

    public function update(Request $request)
    {
        if (Auth::user()->hasPermission('answer_correct')) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'id' => 'required|exists:answers,id',
                'correct' => 'required|boolean'
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $answer = Answer::find($input['id']);
            $answer->correct = $input['correct'];
            $answer->corrected = true;
            $answer->save();
            return $this->sendResponse($answer, 201);
        }
        return $this->sendError('no_permissions', [], 403);
    }
}