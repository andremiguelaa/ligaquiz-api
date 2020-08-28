<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use Behat\Transliterator\Transliterator;
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
use App\Game;

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
                    $quiz->submitted = $quiz->isSubmitted();
                    $round = Round::where('date', $date)->first();
                    if ($round && $round->round !== 10 && $round->round !== 20) {
                        $quiz->solo = false;
                    } else {
                        $quiz->solo = true;
                    }
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
                    unset($quiz->questions);
                    if (
                        array_key_exists('date', $input) &&
                        $input['date'] < $now->format('Y-m-d')
                    ) {
                        $answers = Answer::whereIn(
                            'question_id',
                            $questions->pluck('id')->toArray()
                        )
                            ->where('submitted', 1)
                            ->get()
                            ->groupBy('question_id');
                        $quiz->questions = $questions->map(function ($question) use ($answers) {
                            if (isset($answers[$question->id])) {
                                $question->percentage =
                                    $answers[$question->id]->where('correct', 1)->count() /
                                    $answers[$question->id]->count() *
                                100;
                            }
                            return $question;
                        });
                        $quiz->today = false;
                    } else {
                        $quiz->questions = $questions;
                        $quiz->today = true;
                        if ($round) {
                            $roundGames = Game::where('round_id', $round->id)->get();
                            $game = $roundGames->where('user_id_1', Auth::id())->first();
                            $game = $game ?
                                $game :
                                $roundGames->where('user_id_2', Auth::id())->first();
                            if ($game) {
                                $quiz->game = $game;
                            }
                        }
                    }
                    return $this->sendResponse(
                        ['quiz' => $quiz, 'media' => $media],
                        200
                    );
                }
                return $this->sendError('not_found', [], 404);
            } else {
                if (array_key_exists('past', $input)) {
                    $todayQuiz = Quiz::with('questions.question')->where('date', $now->format('Y-m-d'))->first();
                    if ($todayQuiz && $todayQuiz->isSubmitted()) {
                        $quizzes = Quiz::where('date', '<=', $now->format('Y-m-d'))
                            ->orderBy('date', 'desc')
                            ->get();
                    } else {
                        $quizzes = Quiz::where('date', '<', $now->format('Y-m-d'))
                        ->orderBy('date', 'desc')
                        ->get();
                    }
                } elseif (
                    (
                        !Auth::user()->hasPermission('quiz_create') &&
                        !Auth::user()->hasPermission('quiz_edit') &&
                        !Auth::user()->hasPermission('quiz_delete')
                    )
                ) {
                    $quizzes = Quiz::where('date', '<=', $now->format('Y-m-d'))
                        ->orderBy('date', 'desc')
                        ->get();
                } else {
                    $quizzes = Quiz::orderBy('date', 'desc')->get();
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
        if (Auth::user()->hasPermission('quiz_create')) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'date' => 'required|date_format:Y-m-d|unique:quizzes',
                'questions' => 'required|array|size:8',
                'questions.*.content' => 'nullable|string',
                'questions.*.answer' => 'nullable|string',
                'questions.*.media_id' => 'nullable|exists:media,id',
                'questions.*.genre_id' => [
                    'nullable',
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
                'questions.*.content' => 'nullable|string',
                'questions.*.answer' => 'nullable|string',
                'questions.*.media_id' => 'nullable|exists:media,id',
                'questions.*.genre_id' => [
                    'nullable',
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
            $quiz = Quiz::find($input['id']);
            if ($quiz->hasAnswers()) {
                return $this->sendError('has_answers', null, 400);
            } else {
                $quizQuestions = QuizQuestion::where('quiz_id', $quiz->id)->get();
                Question::whereIn('id', $quizQuestions->pluck('question_id')->toArray())->delete();
                QuizQuestion::where('quiz_id', $quiz->id)->delete();
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
                'answers.*.text' => 'nullable|string',
                'answers.*.points' => ['integer', 'min:0', 'max:3']
            ];
            if (!$solo) {
                array_push($rules['answers.*.points'], 'required');
            }
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $quiz = Quiz::with('questions.question')->where('date', $now)->first();
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
            }
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
                    'text' => $answerText,
                    'points' => !$solo ? $answer['points'] : 0,
                    'user_id' => Auth::id(),
                    'correct' => $correct,
                    'corrected' => $corrected,
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
