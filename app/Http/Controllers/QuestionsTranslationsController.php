<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Request;
use Validator;
use App\Http\Controllers\BaseController as BaseController;
use App\QuestionsTranslations;

class QuestionsTranslationsController extends BaseController
{
    public function get()
    {
        if (Auth::user()->hasPermission('translate')) {
            return $this->sendResponse(QuestionsTranslations::all(), 200);
        }
        return $this->sendError('no_permissions', [], 403);
    }

    public function create(Request $request)
    {
        if (Auth::user()->hasPermission('translate')) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'question_id' => [
                    'required',
                    'exists:questions,id',
                    'unique:questions_translations,question_id'
                ],
                'content' => 'required|string',
                'answer' => 'required|string'
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $input['user_id'] = Auth::user()->id;
            $translation = QuestionsTranslations::create($input);
            return $this->sendResponse($translation, 200);
        }
        return $this->sendError('no_permissions', [], 403);
    }
}
