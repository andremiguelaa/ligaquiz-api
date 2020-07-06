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
use App\QuizQuestion;

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
                $quiz = Quiz::with('quizQuestions.question')
                    ->where('date', $input['date'])
                    ->first();
                $quiz->questions = array_map(function ($question) {
                    return $question['question'];
                }, $quiz->quizQuestions->toArray());
                unset($quiz->quizQuestions);
                if ($quiz) {
                    return $this->sendResponse($quiz, 200);
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
                $quiz = Quiz::with('quizQuestions.question')
                    ->where('date', '<=', $now)->where('date', $input['date'])
                    ->first();
                if ($quiz) {
                    $quiz->questions = array_map(function ($question) {
                        return $question['question'];
                    }, $quiz->quizQuestions->toArray());
                    unset($quiz->quizQuestions);
                    return $this->sendResponse($quiz, 200);
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
                'questions' => 'required|array|size:8',
                'questions.*.content' => 'string',
                'questions.*.answer' => 'string',
                'questions.*.media' => 'string',
                'questions.*.genre_id' => [
                    Rule::exists('genres', 'id')->where(function ($query) {
                        $query->whereNotNull('parent_id');
                    }),
                ],
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $quiz = Quiz::create($input);
            foreach ($input['questions'] as $question) {
                $createdQuestion = Question::create($question);
                QuizQuestion::create([
                    'quiz_id' => $quiz->id,
                    'question_id' => $createdQuestion->id
                ]);
            }
            return $this->sendResponse($quiz, 200);
        }
        return $this->sendError('no_permissions', [], 403);
    }

    public function update(Request $request)
    {
        if (Auth::user()->hasPermission('quiz_edit')) {
            $input = $request::all();
            $id = array_key_exists('id', $input) ? $input['id'] : 0;
            $validator = Validator::make($input, [
                'id' => 'required|exists:quizzes,id',
                'date' => [
                    'date_format:Y-m-d',
                    Rule::unique('quizzes')->ignore($id),
                ],
                'questions' => 'required|array|size:8',
                'questions.*.id' => [
                    'required',
                    'exists:questions,id',
                    Rule::exists('quiz_questions', 'question_id')
                        ->where(function ($query) use($id) {
                            $query->where('quiz_id', $id);
                        }),
                ],
                'questions.*.content' => 'string',
                'questions.*.answer' => 'string',
                'questions.*.media' => 'string',
                'questions.*.genre_id' => [
                    Rule::exists('genres', 'id')->where(function ($query) {
                        $query->whereNotNull('parent_id');
                    }),
                ],
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $quiz = Quiz::find($input['id']);
            foreach ($input['questions'] as $question) {
                $updatedQuestion = Question::find($question['id']);
                $updatedQuestion->fill($question);
                $updatedQuestion->save();
            }
            $quiz->fill($input);
            $quiz->save();
            return $this->sendResponse($quiz, 200);
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
            $quiz = Quiz::with('quizQuestions.question')->find($input['id']);
            if ($quiz->hasAnswers()) {
                return $this->sendError('has_answers', null, 400);
            } else {
                Question::whereIn('id', $quiz->quizQuestions->pluck('id')->toArray())->delete();
                QuizQuestion::whereIn('question_id', $quiz->quizQuestions->pluck('id')->toArray())->delete();
                $quiz->delete();
                return $this->sendResponse();
            }
        }

        return $this->sendError('no_permissions', [], 403);
    }

    public function submit(Request $request)
    {
        if (Auth::user()->hasPermission('quiz_play')) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'answers' => 'required|array|size:8',
                'answers.*.question_id' => 'required|exists:questions,id',
                'answers.*.text' => 'string',
                'answers.*.points' => 'integer|min:0|max:3'
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $now = Carbon::now()->format('Y-m-d');
            $quiz = Quiz::where('date', $now)->first();
            if (!$quiz) {
                return $this->sendError('no_quiz_today', null, 400);
            }
            $diffCount = count(
                array_diff($quiz->question_ids, array_map(function ($item) {
                    return $item['question_id'];
                }, $input['answers']))
            );
            if ($diffCount) {
                return $this->sendError('wrong_quiz', null, 400);
            }
            
            // to do: check if already submitted
            // to do: points validation (versus game)
            // to do: save submitted answers
            return $this->sendError('work_in_progress', null, 501);
        }

        return $this->sendError('no_permissions', [], 403);
    }
}
