<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Behat\Transliterator\Transliterator;
use Request;
use Validator;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use App\Http\Controllers\BaseController as BaseController;
use App\SpecialQuiz;
use App\SpecialQuizQuestion;
use App\Question;
use App\Answer;
use App\Media;

class SpecialQuizController extends BaseController
{
    public function get(Request $request)
    {
        $input = $request::all();
        $authUser = Auth::user();
        if (
            $authUser->hasPermission('specialquiz_create') ||
            $authUser->hasPermission('specialquiz_edit') ||
            $authUser->hasPermission('specialquiz_delete') ||
            $authUser->hasPermission('specialquiz_play')
        ) {
            $now = Carbon::now();
            if (array_key_exists('date', $input) || array_key_exists('today', $input)) {
                $rules = ['date' => ['date_format:Y-m-d']];
                if (
                    !$authUser->hasPermission('specialquiz_create') &&
                    !$authUser->hasPermission('specialquiz_edit') &&
                    !$authUser->hasPermission('specialquiz_delete')
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
                    $quiz->submitted = $quiz->isSubmitted();
                    $questions = $quiz->questions->map(function ($question) {
                        return $question->question;
                    });
                    $mediaIds = $questions->pluck('media_id')->toArray();
                    $media = array_reduce(
                        Media::whereIn('id', $mediaIds)->get()->toArray(),
                        function ($carry, $item) {
                            $mediaFile = $item;
                            unset($mediaFile['id']);
                            $carry[$item['id']] = $mediaFile;
                            return $carry;
                        },
                        []
                    );
                    if ($quiz->past) {
                        $quiz->result = $quiz->getResult();
                    }
                    unset($quiz->questions);
                    $quiz->questions = $questions;
                    return $this->sendResponse(['quiz' => $quiz, 'media' => $media], 200);
                }
                return $this->sendError('not_found', [], 404);
            } else {
                if (array_key_exists('past', $input)) {
                    $todayQuiz = SpecialQuiz::where('date', $now->format('Y-m-d'))->first();
                    if ($todayQuiz && $todayQuiz->isSubmitted()) {
                        $quizzes = SpecialQuiz::where('date', '<=', $now->format('Y-m-d'))
                            ->orderBy('date', 'desc')
                            ->get();
                    } else {
                        $quizzes = SpecialQuiz::where('date', '<', $now->format('Y-m-d'))
                            ->orderBy('date', 'desc')
                            ->get();
                    }
                } elseif (
                    (
                        !$authUser->hasPermission('specialquiz_create') &&
                        !$authUser->hasPermission('specialquiz_edit') &&
                        !$authUser->hasPermission('specialquiz_delete')
                    )
                ) {
                    $quizzes = SpecialQuiz::where('date', '<=', $now)
                        ->orderBy('date', 'desc')
                        ->get();
                } else {
                    $quizzes = SpecialQuiz::orderBy('date', 'desc')->get();
                    $quizzes = $quizzes->map(function ($item) {
                        if (!$item->past) {
                            $item->completed = $item->isCompleted();
                        }
                        return $item;
                    });
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
                'date' => 'required|date_format:Y-m-d|unique:special_quizzes',
                'user_id' => 'nullable|exists:users,id',
                'subject' => 'nullable|string',
                'description' => 'nullable|string',
                'questions' => 'required|array|size:12',
                'questions.*.content' => 'nullable|string',
                'questions.*.answer' => 'nullable|string',
                'questions.*.media_id' => 'nullable|exists:media,id',
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
                'user_id' => 'nullable|exists:users,id',
                'subject' => 'nullable|string',
                'description' => 'nullable|string',
                'questions' => 'required|array|size:12',
                'questions.*.id' => [
                    'required',
                    'exists:questions,id',
                    Rule::exists('special_quiz_questions', 'question_id')
                        ->where(function ($query) use ($id) {
                            $query->where('special_quiz_id', $id);
                        }),
                ],
                'questions.*.content' => 'nullable|string',
                'questions.*.answer' => 'nullable|string',
                'questions.*.media_id' => 'nullable|exists:media,id',
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
            $quiz = SpecialQuiz::find($input['id']);
            if ($quiz->hasAnswers()) {
                return $this->sendError('has_answers', null, 400);
            } else {
                $quizQuestions = SpecialQuizQuestion::where('special_quiz_id', $quiz->id)->get();
                Question::whereIn('id', $quizQuestions->pluck('question_id')->toArray())->delete();
                SpecialQuizQuestion::where('special_quiz_id', $quiz->id)->delete();
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
                'answers.*.text' => 'nullable|string',
                'answers.*.points' => 'integer|min:0|max:1'
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $now = Carbon::now()->format('Y-m-d');
            $quiz = SpecialQuiz::with('questions.question')->where('date', $now)->first();
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
            $keyedQuestions = $quiz->questions->keyBy('question_id');
            foreach ($input['answers'] as $answer) {
                $answerText = isset($answer['text']) ? $answer['text'] : '';
                $sluggedAnswerText = Transliterator::urlize(str_replace(' ', '', $answerText));
                $sluggedCorrectAnswer = Transliterator::urlize(
                    str_replace(
                        ' ',
                        '',
                        $keyedQuestions[$answer['question_id']]->question->answer
                    )
                );
                $corrected = 0;
                $correct = 0;
                if ($sluggedAnswerText === '') {
                    $corrected = 1;
                    $correct = 0;
                } elseif ($sluggedAnswerText === $sluggedCorrectAnswer) {
                    $corrected = 1;
                    $correct = 1;
                } else {
                    $previousAnswers = Answer::where(
                        'question_id',
                        $answer['question_id']
                    )
                        ->where('submitted', 1)
                        ->where('corrected', 1)
                        ->get();
                    foreach ($previousAnswers as $previousAnswer) {
                        $sluggedPreviousAnswer =Transliterator::urlize(str_replace(
                            ' ',
                            '',
                            $previousAnswer->text
                        ));
                        if ($sluggedPreviousAnswer === $sluggedAnswerText) {
                            $corrected = 1;
                            $correct = $previousAnswer->correct;
                            break;
                        }
                    }
                }
                array_push($answersToSubmit, [
                    'question_id' => $answer['question_id'],
                    'text' => isset($answer['text']) ? $answer['text'] : '',
                    'points' => isset($answer['points']) ? $answer['points'] : 0,
                    'user_id' => Auth::id(),
                    'corrected' => $corrected,
                    'correct' => $correct,
                    'submitted' => 1,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
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
