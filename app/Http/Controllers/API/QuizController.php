<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use Request;
use Validator;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Quiz;
use App\QuizQuestion;
use App\Question;
use App\Answer;
use App\Round;
use App\Media;

class QuizController extends BaseController
{
    public function get(Request $request)
    {
        $input = $request::all();
        if (
            Auth::user()->hasPermission('quiz_create') ||
            Auth::user()->hasPermission('quiz_edit') ||
            Auth::user()->hasPermission('quiz_delete') ||
            Auth::user()->hasPermission('quiz_play')
        ) {
            $now = Carbon::now();
            if (array_key_exists('date', $input) || array_key_exists('today', $input)) {
                $rules = ['date' => ['date_format:Y-m-d']];
                if (
                    !Auth::user()->hasPermission('quiz_create') &&
                    !Auth::user()->hasPermission('quiz_edit') &&
                    !Auth::user()->hasPermission('quiz_delete')
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
                $quiz = Quiz::with('questions.question')->where('date', $date)->first();
                if ($quiz) {
                    $questions = $quiz->questions->map(function ($question) {
                        return $question->question;
                    });
                    $mediaIds = $questions->pluck('media_id')->toArray();
                    $media = Media::whereIn('id', $mediaIds)->get()->toArray();
                    unset($quiz->questions);
                    $quiz->questions = $questions;
                    // todo: show percentage for past quizzes
                    return $this->sendResponse(
                        ['quiz' => $quiz, 'media' => $media],
                        200
                    );
                }
                return $this->sendError('not_found', [], 404);
            } else {
                if (
                    !Auth::user()->hasPermission('quiz_create') &&
                    !Auth::user()->hasPermission('quiz_edit') &&
                    !Auth::user()->hasPermission('quiz_delete')
                ) {
                    $quizzes = Quiz::where('date', '<=', $now)->get();
                } else {
                    $quizzes = Quiz::all();
                }
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
            $quiz = Quiz::with('questions.question')->find($quiz->id);
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
                        ->where(function ($query) use ($id) {
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
            $quiz = Quiz::with('questions.question')->find($quiz->id);
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
        if (Auth::user()->hasPermission('quiz_delete')) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'id' => 'required|exists:quizzes,id',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $quiz = Quiz::with('questions', 'questions.question')->find($input['id']);
            if ($quiz->hasAnswers()) {
                return $this->sendError('has_answers', null, 400);
            } else {
                Question::whereIn('id', $quiz->questions->pluck('id')->toArray())->delete();
                QuizQuestion::whereIn('question_id', $quiz->questions->pluck('id')
                    ->toArray())
                    ->delete();
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
            $now = Carbon::now()->format('Y-m-d');
            $round = Round::where('date', $now)->first();
            $solo = true;
            if ($round && $round->round !== 10 && $round->round !== 20) {
                $solo = false;
            }
            $rules = [
                'answers' => 'required|array|size:8',
                'answers.*.question_id' => 'required|exists:questions,id',
                'answers.*.text' => 'string',
                'answers.*.points' => ['integer', 'min:0', 'max:3']
            ];
            if (!$solo) {
                array_push($rules['answers.*.points'], 'required');
            }
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $quiz = Quiz::where('date', $now)->first();
            if (!$quiz) {
                return $this->sendError('validation_error', ['no_quiz_today'], 400);
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
                return $this->sendError('validation_error', ['wrong_quiz'], 400);
            }
            $answers = Answer::whereIn('question_id', $questionIds)
                ->where('user_id', Auth::id())
                ->where('submitted', 1)
                ->first();
            if ($answers) {
                return $this->sendError('validation_error', ['already_submitted'], 409);
            }
            $answersToSubmit = [];
            if (!$solo) {
                $points = [
                    0 => 0,
                    1 => 0,
                    2 => 0,
                    3 => 0
                ];
                foreach ($input['answers'] as $answer) {
                    $points[$answer['points']]++;
                }
                if ($points !== [0=>1, 1=>3, 2=>3, 3=>1]) {
                    return $this->sendError(
                        'validation_error',
                        ["answers" => 'invalid_points'],
                        400
                    );
                }
                foreach ($input['answers'] as $answer) {
                    array_push($answersToSubmit, $this->generateAnswerToSubmit([
                        'question_id' => $answer['question_id'],
                        'text' => isset($answer['text']) ? $answer['text'] : '',
                        'points' => $answer['points']
                    ]));
                }
            } else {
                foreach ($input['answers'] as $answer) {
                    array_push($answersToSubmit, $this->generateAnswerToSubmit([
                        'question_id' => $answer['question_id'],
                        'text' => isset($answer['text']) ? $answer['text'] : '',
                        'points' => 0
                    ]));
                }
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

    private function generateAnswerToSubmit($answer)
    {
        $answerToSubmit = [
            'question_id' => $answer['question_id'],
            'text' => $answer['text'],
            'points' => $answer['points'],
            'user_id' => Auth::id(),
            'correct' => 0,
            'corrected' => 0,
            // todo: autocorrect
            'submitted' => 1,
        ];
        return $answerToSubmit;
    }
}
