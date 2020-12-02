<?php

namespace App\Http\Controllers;

use Request;
use Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Http\Controllers\BaseController as BaseController;
use App\Question;
use App\QuizQuestion;
use App\Quiz;
use App\SpecialQuizQuestion;
use App\SpecialQuiz;
use App\Round;
use App\Genre;
use App\QuestionsTranslations;

class QuestionController extends BaseController
{
    public function get(Request $request)
    {
        if (
            Auth::user()->isAdmin() ||
            Auth::user()->hasPermission('quiz_create') ||
            Auth::user()->hasPermission('quiz_edit') ||
            Auth::user()->hasPermission('quiz_play') ||
            Auth::user()->hasPermission('specialquiz_play') ||
            Auth::user()->hasPermission('translate')
        ) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'id' => 'array|exists:questions',
                'search' => 'nullable|string',
                'search_field' => [
                    'nullable',
                    Rule::in(['content', 'answer'])
                ],
                'genre' => 'exists:genres,id',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            if (
                (
                    !isset($input['id']) ||
                    isset($input['search']) ||
                    isset($input['genre'])
                ) &&
                !(
                    Auth::user()->isAdmin() ||
                    Auth::user()->hasPermission('quiz_create') ||
                    Auth::user()->hasPermission('quiz_edit') ||
                    Auth::user()->hasPermission('translate')
                )
            ) {
                return $this->sendError('no_permissions', [], 403);
            }
            if (array_key_exists('search', $input) || isset($input['genre'])) {
                $search = isset($input['search']) ? $input['search'] : '';
                $search = mb_strtolower($search);
                $searchField = isset($input['search_field']) ? $input['search_field'] : null;
                $questions = Question::where(function ($query) use ($search, $searchField) {
                    if ($searchField === 'content') {
                        $query->whereRaw('LOWER(content) LIKE BINARY "%'.$search.'%"');
                    } elseif ($searchField === 'answer') {
                        $query->whereRaw('LOWER(answer) LIKE BINARY "%'.$search.'%"');
                    } else {
                        $query->whereRaw('LOWER(content) LIKE BINARY "%'.$search.'%"')
                        ->orWhereRaw('LOWER(answer) LIKE BINARY "%'.$search.'%"');
                    }
                });
                if (isset($input['genre'])) {
                    $genres = Genre::where('id', $input['genre'])
                        ->orWhere('parent_id', $input['genre'])
                        ->get()
                        ->pluck('id')
                        ->toArray();
                    $questions = $questions->whereIn('genre_id', $genres);
                }
                $questions = $questions->paginate(10);
                $questionIds = $questions->pluck('id');
                $quizQuestions = QuizQuestion::whereIn('question_id', $questionIds)->get();
                $specialQuizQuestions = SpecialQuizQuestion::whereIn('question_id', $questionIds)->get();
                $quizQuestionsQuiz = $quizQuestions->groupBy('question_id')->map(function ($item) {
                    return $item[0]->quiz_id;
                })->toArray();
                $specialQuizQuestionsQuiz = $specialQuizQuestions->groupBy('question_id')->map(function ($item) {
                    return $item[0]->special_quiz_id;
                })->toArray();
                $quizzes = Quiz::whereIn('id', $quizQuestionsQuiz)->get()->groupBy('id');
                $specialQuizzes = SpecialQuiz::whereIn('id', $specialQuizQuestionsQuiz)->get()->groupBy('id');
                $questions->getCollection()->transform(
                    function ($question) use (
                        $quizQuestionsQuiz,
                        $specialQuizQuestionsQuiz,
                        $quizzes,
                        $specialQuizzes
                    ) {
                        if (isset($quizQuestionsQuiz[$question->id])) {
                            $question->quiz = [
                                "type" => 'quiz',
                                "date" => $quizzes[$quizQuestionsQuiz[$question->id]][0]->date
                            ];
                        } else {
                            $question->quiz = [
                                "type" => 'special_quiz',
                                "date" => $specialQuizzes[$specialQuizQuestionsQuiz[$question->id]][0]->date
                            ];
                        }
                        return $question;
                    }
                );
                if (Auth::user()->hasPermission('translate')) {
                    $translations = QuestionsTranslations::whereIn('question_id', $questionIds)
                        ->select('question_id', 'user_id', 'used')
                        ->get()
                        ->keyBy('question_id');
                    $questions->getCollection()->transform(
                        function ($question) use ($translations) {
                            if (isset($translations[$question->id])) {
                                $question->translated = true;
                                $question->translator = $translations[$question->id]['user_id'];
                                $question->translation_used = $translations[$question->id]['used'];
                            }
                            else {
                                $question->translated = false;
                            }
                            return $question;
                        }
                    );
                }
                $response = $questions;
            } elseif (isset($input['id'])) {
                if (count($input['id']) === 1) {
                    $response = Question::with(['submittedAnswers', 'media'])
                        ->find($input['id'][0]);
                    $quizQuestion = QuizQuestion::where('question_id', $input['id'][0])->first();
                    if ($quizQuestion) {
                        if (
                            !(
                                Auth::user()->isAdmin() ||
                                Auth::user()->hasPermission('quiz_create') ||
                                Auth::user()->hasPermission('quiz_edit') ||
                                Auth::user()->hasPermission('quiz_play')
                            )
                        ) {
                            return $this->sendError('no_permissions', [], 403);
                        }
                        $date = Quiz::find($quizQuestion->quiz_id)->date;
                        $round = Round::where('date', $date)->first();
                        if ($round && $round->round !== 10 && $round->round !== 20) {
                            $response->type = 'versus';
                        } else {
                            $response->type = 'solo';
                        }
                    } else {
                        $specialQuizId = SpecialQuizQuestion::where('question_id', $input['id'][0])
                            ->first()
                            ->special_quiz_id;
                        $date = SpecialQuiz::find($specialQuizId)->date;
                        $response->type = 'special';
                    }
                    if ($date >= Carbon::now()->format('Y-m-d')) {
                        return $this->sendError('no_permissions', [], 403);
                    }
                    $response->date = $date;
                    $response->answers = $response->submittedAnswers->map(function ($item) {
                        $item->makeHidden('id');
                        $item->makeHidden('question_id');
                        $item->makeHidden('submitted');
                        $item->makeHidden('text');
                        return $item;
                    });
                    unset($response->submittedAnswers);
                    unset($response->media_id);
                    if (isset($response->media)) {
                        unset($response->media->id);
                    }
                } else {
                    $response = Question::whereIn('id', $input['id'])->get();
                }
            } else {
                $response = Question::paginate(10);
            }
            return $this->sendResponse($response, 200);
        }

        return $this->sendError('no_permissions', [], 403);
    }
}
