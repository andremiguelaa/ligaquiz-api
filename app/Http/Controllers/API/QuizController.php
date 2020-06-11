<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use Request;
use Validator;
use Illuminate\Validation\Rule;
// use Carbon\Carbon;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Quiz;
use App\Question;
use App\Http\Resources\Quiz as QuizResource;

class QuizController extends BaseController
{
    public function get()
    {
        if (Auth::user()->hasPermission('quiz_create') || Auth::user()->hasPermission('quiz_play')) {
            return $this->sendResponse(Quiz::all(), 200);
        }
        return $this->sendError('no_permissions', [], 403);
    }

    public function create(Request $request)
    {
        if (Auth::user()->hasPermission('quiz_create')) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'date' => 'required|date_format:Y-m-d|unique:quizzes',
                'questions' => 'required|array|size:8'
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            if (array_key_exists('questions', $input)) {
                foreach ($input['questions'] as $question) {
                    $questionValidator = Validator::make($question, [
                    'text' => 'string',
                    'answer' => 'string',
                    // 'media' => 'string',
                    'genre_id' => [
                        Rule::exists('genres', 'id')->where(function ($query) {
                            $query->whereNotNull('parent_id');
                        }),
                    ],
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
            $quiz = Quiz::create($input);
            return $this->sendResponse(new QuizResource($quiz), 200);
        }
        return $this->sendError('no_permissions', [], 403);
    }
}
