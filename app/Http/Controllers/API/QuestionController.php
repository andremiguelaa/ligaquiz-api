<?php

namespace App\Http\Controllers\API;

use Request;
use Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Question;
use App\QuizQuestion;
use App\Quiz;
use App\SpecialQuizQuestion;
use App\SpecialQuiz;
use App\Round;

class QuestionController extends BaseController
{
    public function get(Request $request)
    {
        if (
            Auth::user()->isAdmin() ||
            Auth::user()->hasPermission('quiz_create') ||
            Auth::user()->hasPermission('quiz_edit') ||
            Auth::user()->hasPermission('quiz_play') ||
            Auth::user()->hasPermission('specialquiz_play')
        ) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'id' => 'array|exists:questions',
                'search' => 'string'
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            if (
                (
                    !isset($input['id']) ||
                    isset($input['search'])
                ) &&
                !(
                    Auth::user()->isAdmin() ||
                    Auth::user()->hasPermission('quiz_create') ||
                    Auth::user()->hasPermission('quiz_edit')
                )
            ) {
                return $this->sendError('no_permissions', [], 403);
            }
            if (isset($input['search'])) {
                $query = mb_strtolower($input['search']);
                $response = Question::whereRaw('LOWER(content) LIKE BINARY "%'.$query.'%"')
                    ->orWhereRaw('LOWER(answer) LIKE BINARY "%'.$query.'%"')
                    ->get();
            } elseif (isset($input['id'])) {
                if (count($input['id']) === 1) {
                    $response = Question::with(['submittedAnswers', 'media'])
                        ->find($input['id'][0]);
                    $response = $response->first();
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
                $response = Question::all();
            }
            return $this->sendResponse($response, 200);
        }

        return $this->sendError('no_permissions', [], 403);
    }
}
