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

class QuestionController extends BaseController
{
    public function get(Request $request)
    {
        if (
            Auth::user()->isAdmin() ||
            Auth::user()->hasPermission('quiz_create') ||
            Auth::user()->hasPermission('quiz_edit') ||
            Auth::user()->hasPermission('quiz_play')
        ) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'id' => 'exists:questions',
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
                $response = Question::with(['submittedAnswers', 'media'])
                    ->find($input['id']);
                $quizQuestion = QuizQuestion::where('question_id', $input['id'])->first();
                if ($quizQuestion) {
                    $date = Quiz::find($quizQuestion->quiz_id)->date;
                } else {
                    $specialQuizId = SpecialQuizQuestion::where('question_id', $input['id'])
                        ->first()
                        ->special_quiz_id;
                    $date = SpecialQuiz::find($specialQuizId)->date;
                }
                if ($date >= Carbon::now()->format('Y-m-d')) {
                    return $this->sendError('no_permissions', [], 403);
                }
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
                $response = Question::all();
            }
            return $this->sendResponse($response, 200);
        }

        return $this->sendError('no_permissions', [], 403);
    }
}
