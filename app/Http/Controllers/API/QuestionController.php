<?php

namespace App\Http\Controllers\API;

use Request;
use Validator;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Question;

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
                $response->answers = $response->submittedAnswers->map(function ($item) {
                    $item->makeHidden('id');
                    $item->makeHidden('question_id');
                    $item->makeHidden('submitted');
                    $item->makeHidden('text');
                    return $item;
                });
                unset($response->submittedAnswers);
                unset($response->media_id);
                unset($response->media->id);
            } else {
                $response = Question::all();
            }
            return $this->sendResponse($response, 200);
        }

        return $this->sendError('no_permissions', [], 403);
    }
}
