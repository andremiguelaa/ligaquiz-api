<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use Request;
use Validator;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use App\Http\Controllers\API\BaseController as BaseController;
use App\SpecialQuiz;
use App\SpecialQuizQuestion;
use App\Question;
use App\Answer;

class SpecialQuizController extends BaseController
{
    public function get(Request $request)
    {
        $input = $request::all();
        if (
            Auth::user()->hasPermission('specialquiz_create') ||
            Auth::user()->hasPermission('specialquiz_edit') ||
            Auth::user()->hasPermission('specialquiz_delete') ||
            Auth::user()->hasPermission('specialquiz_play')
        ) {
            $now = Carbon::now();
            if (array_key_exists('date', $input) || array_key_exists('today', $input)) {
                $rules = ['date' => ['date_format:Y-m-d']];
                if (
                    !Auth::user()->hasPermission('specialquiz_create') &&
                    !Auth::user()->hasPermission('specialquiz_edit') &&
                    !Auth::user()->hasPermission('specialquiz_delete')
                ) {
                    array_push($rules['date'], 'before_or_equal:'.$now->format('Y-m-d'));
                }
                $validator = Validator::make($input, $rules);
                if ($validator->fails()) {
                    return $this->sendError('validation_error', $validator->errors(), 400);
                }
                if (array_key_exists('today', $input)) {
                    $date = $now->format('Y-m-d');
                } else {
                    $date = $input['date'];
                }
                $quiz = SpecialQuiz::with('questions.question')->where('date', $date)->first();
                if ($quiz) {
                    $questions = $quiz->questions->map(function ($question) {
                        return $question->question;
                    });
                    unset($quiz->questions);
                    $quiz->questions = $questions;
                    // todo: show percentage and classification for past quizzes
                    return $this->sendResponse($quiz, 200);
                }
                return $this->sendError('not_found', [], 404);
            } else {
                if (
                    (
                        !Auth::user()->hasPermission('specialquiz_create') &&
                        !Auth::user()->hasPermission('specialquiz_edit') &&
                        !Auth::user()->hasPermission('specialquiz_delete')
                    )
                ) {
                    $quizzes = SpecialQuiz::where('date', '<=', $now)
                        ->orderBy('date', 'desc')
                        ->get();
                } elseif (array_key_exists('past', $input)) {
                    $quizzes = SpecialQuiz::where('date', '<', $now)
                        ->orderBy('date', 'desc')
                        ->get();
                } else {
                    $quizzes = SpecialQuiz::orderBy('date', 'desc')->get();
                }
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
            $quiz = SpecialQuiz::create($input);
            foreach ($input['questions'] as $question) {
                $createdQuestion = Question::create($question);
                SpecialQuizQuestion::create([
                    'special_quiz_id' => $quiz->id,
                    'question_id' => $createdQuestion->id
                ]);
            }

            $quiz = SpecialQuiz::with('questions.question')->find($quiz->id);
            $questions = $quiz->questions->map(function ($question) {
                return $question->question;
            });
            unset($quiz->questions);
            $quiz->questions = $questions;
            return $this->sendResponse($quiz, 200);
        }
        return $this->sendError('no_permissions', [], 403);
    }

    public function update(Request $request)
    {
        if (Auth::user()->hasPermission('specialquiz_edit')) {
            $input = $request::all();
            $id = array_key_exists('id', $input) ? $input['id'] : 0;
            $validator = Validator::make($input, [
                'id' => 'required|exists:special_quizzes,id',
                'date' => [
                    'date_format:Y-m-d',
                    Rule::unique('special_quizzes')->ignore($input['id']),
                ],
                'user_id' => 'exists:users,id',
                'subject' => 'string',
                'description' => 'string',
                'questions' => 'required|array|size:12',
                'questions.*.id' => [
                    'required',
                    'exists:questions,id',
                    Rule::exists('special_quiz_questions', 'question_id')
                        ->where(function ($query) use ($id) {
                            $query->where('special_quiz_id', $id);
                        }),
                ],
                'questions.*.content' => 'string',
                'questions.*.answer' => 'string',
                'questions.*.media' => 'string',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $quiz = SpecialQuiz::find($input['id']);
            foreach ($input['questions'] as $question) {
                $updatedQuestion = Question::find($question['id']);
                $updatedQuestion->fill($question);
                $updatedQuestion->save();
            }
            $quiz->fill($input);
            $quiz->save();
            $quiz = SpecialQuiz::with('questions.question')->find($quiz->id);
            $questions = $quiz->questions->map(function ($question) {
                return $question->question;
            });
            unset($quiz->questions);
            $quiz->questions = $questions;
            return $this->sendResponse($quiz, 200);
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
            $quiz = SpecialQuiz::with('questions', 'questions.question')->find($input['id']);
            if ($quiz->hasAnswers()) {
                return $this->sendError('has_answers', null, 400);
            } else {
                Question::whereIn('id', $quiz->questions->pluck('id')->toArray())->delete();
                SpecialQuizQuestion::whereIn(
                    'question_id',
                    $quiz->questions->pluck('id')->toArray()
                )->delete();
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
                return $this->sendError('validation_error', ['no_specialquiz_today'], 400);
            }
            $questionIds = $quiz->questions->pluck('question_id')->toArray();
            $diffCount = count(
                array_diff(
                    $questionIds,
                    array_map(
                        function ($item) {
                            return $item['question_id'];
                        },
                        $input['answers']
                    )
                )
            );
            if ($diffCount) {
                return $this->sendError('validation_error', ['wrong_specialquiz'], 400);
            }
            $answers = Answer::whereIn('question_id', $questionIds)
            ->where('user_id', Auth::id())
            ->where('submitted', 1)
            ->first();
            if ($answers) {
                return $this->sendError('validation_error', ['already_submitted'], 409);
            }
            $jokers = 0;
            foreach ($input['answers'] as $answer) {
                if (isset($answer['points']) && $answer['points'] > 0) {
                    $jokers++;
                }
            }
            if ($jokers > 5) {
                return $this->sendError(
                    'validation_error',
                    ["answers" => 'too_much_jokers'],
                    400
                );
            }
            $answersToSubmit = [];
            foreach ($input['answers'] as $answer) {
                array_push($answersToSubmit, [
                    'question_id' => $answer['question_id'],
                    'text' => isset($answer['text']) ? $answer['text'] : '',
                    'points' => isset($answer['points']) ? $answer['points'] : 0,
                    'user_id' => Auth::id(),
                    'correct' => 0,
                    'corrected' => 0,
                    // todo: autocorrect
                    'submitted' => 1,
                ]);
            }
            Answer::insert($answersToSubmit);
            $submittedAnswers = Answer::whereIn('question_id', $questionIds)
                ->where('user_id', Auth::id())
                ->where('submitted', 1)
                ->get();
            $submittedAnswers->makeHidden('id');
            $submittedAnswers->makeHidden('user_id');
            $submittedAnswers->makeHidden('submitted');
            return $this->sendResponse($submittedAnswers, 201);
        }
        return $this->sendError('no_permissions', [], 403);
    }
}
