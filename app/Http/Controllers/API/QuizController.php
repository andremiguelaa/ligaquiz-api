<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use Request;
use Validator;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Quiz;
use App\Question;
use App\Http\Resources\Quiz as QuizResource;

class QuizController extends BaseController
{
    public function get(Request $request)
    {
        $input = $request::all();
        if (
            Auth::user()->hasPermission('quiz_create') ||
            Auth::user()->hasPermission('quiz_edit') ||
            Auth::user()->hasPermission('quiz_delete')
        ) {
            if (array_key_exists('date', $input)) {
                $validator = Validator::make($input, [
                    'date' => 'date_format:Y-m-d',
                ]);
                if ($validator->fails()) {
                    return $this->sendError('validation_error', $validator->errors(), 400);
                }
                $quiz = Quiz::where('date', $input['date'])->first();
                if ($quiz) {
                    return $this->sendResponse(new QuizResource($quiz), 200);
                }
                return $this->sendError('not_found', [], 404);
            } else {
                return $this->sendResponse(Quiz::all(), 200);
            }
        } elseif (Auth::user()->hasPermission('quiz_play')) {
            $now = Carbon::now();
            if (array_key_exists('date', $input)) {
                $validator = Validator::make($input, [
                    'date' => 'date_format:Y-m-d',
                ]);
                if ($validator->fails()) {
                    return $this->sendError('validation_error', $validator->errors(), 400);
                }
                $quiz = Quiz::where('date', '<=', $now)->where('date', $input['date'])->first();
                if ($quiz) {
                    return $this->sendResponse(new QuizResource($quiz), 200);
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
        if (Auth::user()->hasPermission('quiz_create')) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'date' => 'date_format:Y-m-d|unique:quizzes',
                'questions' => 'required|array|size:8'
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
                        'genre_id' => [
                            Rule::exists('genres', 'id')->where(function ($query) {
                                $query->whereNotNull('parent_id');
                            }),
                        ],
                    ]);
                    if ($questionValidator->fails()) {
                        return $this->sendError(
                            'validation_error',
                            ['questions' => 'validation.format'],
                            400
                        );
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

    public function update(Request $request)
    {
        if (Auth::user()->hasPermission('quiz_edit')) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'id' => 'required|exists:quizzes,id',
                'date' => [
                    'date_format:Y-m-d',
                    Rule::unique('quizzes')->ignore($input['id']),
                ],
                'questions' => 'required|array|size:8'
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $quiz = Quiz::find($input['id']);
            if (array_key_exists('questions', $input)) {
                foreach ($input['questions'] as $question) {
                    $questionValidator = Validator::make($question, [
                        'id' => 'required|exists:questions,id',
                        'content' => 'string',
                        'answer' => 'string',
                        'media' => 'string',
                        'genre_id' => [
                            Rule::exists('genres', 'id')->where(function ($query) {
                                $query->whereNotNull('parent_id');
                            }),
                        ],
                    ]);
                    if ($questionValidator->fails()) {
                        return $this->sendError(
                            'validation_error',
                            ['questions' => 'validation.format'],
                            400
                        );
                    }
                }
                $diffCount = count(
                    array_diff($quiz->question_ids, array_map(function ($item) {
                        return $item['id'];
                    }, $input['questions']))
                );
                if ($quiz->hasAnswers() && $diffCount) {
                    return $this->sendError('has_answers', null, 400);
                }
            }
            $input['question_ids'] = [];
            foreach ($input['questions'] as $question) {
                $updatedQuestion = Question::find($question['id']);
                $updatedQuestion->fill($question);
                $updatedQuestion->save();
                array_push($input['question_ids'], $updatedQuestion->id);
            }
            $quiz->fill($input);
            $quiz->save();
            return $this->sendResponse(new QuizResource($quiz), 200);
        }
        return $this->sendError('no_permissions', [], 403);
    }

    public function delete(Request $request)
    {
        if (Auth::user()->hasPermission('quiz_delete')) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'id' => 'required|exists:quizzes,id',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $quiz = Quiz::find($input['id']);
            if ($quiz->hasAnswers()) {
                return $this->sendError('has_answers', null, 400);
            } else {
                $quiz->delete();
                return $this->sendResponse();
            }
        }

        return $this->sendError('no_permissions', [], 403);
    }
}
