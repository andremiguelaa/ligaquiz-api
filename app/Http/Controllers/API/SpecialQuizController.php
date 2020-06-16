<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use Request;
use Validator;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use App\Http\Controllers\API\BaseController as BaseController;
use App\SpecialQuiz;
use App\Question;
use App\Http\Resources\SpecialQuiz as SpecialQuizResource;

class SpecialQuizController extends BaseController
{
    public function get(Request $request)
    {
        $input = $request::all();
        if (
            Auth::user()->hasPermission('specialquiz_create') ||
            Auth::user()->hasPermission('specialquiz_edit') ||
            Auth::user()->hasPermission('specialquiz_delete')
        ) {
            if (array_key_exists('date', $input)) {
                $validator = Validator::make($input, [
                    'date' => 'date_format:Y-m-d',
                ]);
                if ($validator->fails()) {
                    return $this->sendError('validation_error', $validator->errors(), 400);
                }
                $quiz = SpecialQuiz::where('date', $input['date'])->first();
                if ($quiz) {
                    return $this->sendResponse(new SpecialQuizResource($quiz), 200);
                }
                return $this->sendError('not_found', [], 404);
            } else {
                return $this->sendResponse(SpecialQuiz::all(), 200);
            }
        } elseif (Auth::user()->hasPermission('specialquiz_play')) {
            $now = Carbon::now();
            if (array_key_exists('date', $input)) {
                $validator = Validator::make($input, [
                    'date' => 'date_format:Y-m-d',
                ]);
                if ($validator->fails()) {
                    return $this->sendError('validation_error', $validator->errors(), 400);
                }
                $quiz = SpecialQuiz::where('date', '<=', $now)->where('date', $input['date'])->first();
                if ($quiz) {
                    return $this->sendResponse(new SpecialQuizResource($quiz), 200);
                }
                return $this->sendError('not_found', [], 404);
            } else {
                $quizzes = Quiz::where('date', '<=', $now)->get();
                return $this->sendResponse($quizzes, 200);
            }
        }
        return $this->sendError('no_permissions', [], 403);
    }

    public function create(Request $request)
    {
        if (Auth::user()->hasPermission('specialquiz_create')) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'date' => 'date_format:Y-m-d|unique:special_quizzes',
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
                    'content' => 'string',
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

    public function update(Request $request)
    {
        if (Auth::user()->hasPermission('specialquiz_edit')) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'date' => [
                    'date_format:Y-m-d',
                    Rule::unique('special_quizzes')->ignore($input['id']),
                ],
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
                    'id' => 'required|exists:questions,id',
                    'content' => 'string',
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
                $updatedQuestion = Question::find($question['id']);
                $updatedQuestion->fill($question);
                $updatedQuestion->save();
                array_push($input['question_ids'], $updatedQuestion->id);
            }            
            $quiz = SpecialQuiz::find($input['id']);
            $quiz->fill($input);
            $quiz->save();
            return $this->sendResponse(new SpecialQuizResource($quiz), 200);
        }
        return $this->sendError('no_permissions', [], 403);
    }
}
