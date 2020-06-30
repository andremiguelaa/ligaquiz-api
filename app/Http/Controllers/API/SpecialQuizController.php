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
                'questions' => 'required|array|size:12',
                'questions.*.content' => 'string',
                'questions.*.answer' => 'string',
                'questions.*.media' => 'string',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
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
                'questions' => 'required|array|size:12',
                'questions.*.id' => 'required|exists:questions,id',
                'questions.*.content' => 'string',
                'questions.*.answer' => 'string',
                'questions.*.media' => 'string',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $quiz = SpecialQuiz::find($input['id']);
            $diffCount = count(
                array_diff($quiz->question_ids, array_map(function ($item) {
                    return $item['id'];
                }, $input['questions']))
            );
            if ($quiz->hasAnswers() && $diffCount) {
                return $this->sendError('has_answers', null, 400);
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
            return $this->sendResponse(new SpecialQuizResource($quiz), 200);
        }
        return $this->sendError('no_permissions', [], 403);
    }

    public function delete(Request $request)
    {
        if (Auth::user()->hasPermission('specialquiz_delete')) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'id' => 'required|exists:special_quizzes,id',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $quiz = SpecialQuiz::find($input['id']);
            if ($quiz->hasAnswers()) {
                return $this->sendError('has_answers', null, 400);
            } else {
                $quiz->delete();
                return $this->sendResponse();
            }
        }

        return $this->sendError('no_permissions', [], 403);
    }

    public function submit(Request $request)
    {
        if (Auth::user()->hasPermission('specialquiz_play')) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'answers' => 'required|array|size:12',
                'answers.*.question_id' => 'required|exists:questions,id',
                'answers.*.text' => 'string',
                'answers.*.points' => 'integer|min:0|max:1'
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $now = Carbon::now()->format('Y-m-d');
            $quiz = SpecialQuiz::where('date', $now)->first();
            if (!$quiz) {
                return $this->sendError('no_specialquiz_today', null, 400);
            }
            $diffCount = count(
                array_diff($quiz->question_ids, array_map(function ($item) {
                    return $item['question_id'];
                }, $input['answers']))
            );
            if ($diffCount) {
                return $this->sendError('wrong_specialquiz', null, 400);
            }
            
            // to do: check if already submitted
            // to do: save submitted answers
            return $this->sendError('work_in_progress', null, 501);
        }

        return $this->sendError('no_permissions', [], 403);
    }
}
