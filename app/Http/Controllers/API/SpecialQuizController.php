<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use Request;
use Validator;
use App\Http\Controllers\API\BaseController as BaseController;
use App\SpecialQuiz;
use App\Question;
use App\Http\Resources\SpecialQuiz as SpecialQuizResource;

class SpecialQuizController extends BaseController
{
    public function get()
    {
        if (Auth::user()->hasPermission('specialquiz_create') || Auth::user()->hasPermission('specialquiz_play')) {
            return $this->sendResponse(SpecialQuiz::all(), 200);
        }
        return $this->sendError('no_permissions', [], 403);
    }

    public function create(Request $request)
    {
        if (Auth::user()->hasPermission('specialquiz_create')) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'date' => 'required|date_format:Y-m-d|unique:special_quizzes',
                'user_id' => 'exists:users,id',
                'subject' => 'string',
                'description' => 'string',
                'questions' => 'required|array|size:12'
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            if (array_key_exists('questions', $input)) {
                foreach ($input['questions'] as $question) {
                    $questionValidator = Validator::make($question, [
                    'text' => 'string',
                    'answer' => 'string',
                    'media' => 'string',
                ]);
                    if ($questionValidator->fails()) {
                        return $this->sendError('validation_error', ['questions' => 'validation.format'], 400);
                    }
                }
            }
            $input['question_ids'] = [];
            foreach ($input['questions'] as $question) {
                $question = Question::create($question);
                array_push($input['question_ids'], $question->id);
            }
            $quiz = SpecialQuiz::create($input);
            return $this->sendResponse(new SpecialQuizResource($quiz), 200);
        }
        return $this->sendError('no_permissions', [], 403);
    }
}
