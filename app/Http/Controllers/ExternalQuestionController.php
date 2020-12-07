<?php

namespace App\Http\Controllers;

use Request;
use Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\BaseController as BaseController;
use App\ExternalQuestion;

class ExternalQuestionController extends BaseController
{
    public function get(Request $request)
    {
        if (
            Auth::user()->isAdmin() ||
            Auth::user()->hasPermission('quiz_create') ||
            Auth::user()->hasPermission('quiz_edit')
        ) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'search' => 'nullable|string',
                'search_field' => [
                    'nullable',
                    Rule::in(
                        [
                            'formulation',
                            'answer'
                        ]
                    )
                ],
                'genre' => [
                    Rule::in(
                        [
                            'culture',
                            'entertainment',
                            'history',
                            'lifestyle',
                            'media',
                            'sport',
                            'science',
                            'world'
                        ]
                    )
                ],
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            if (array_key_exists('search', $input) || isset($input['genre'])) {
                $search = isset($input['search']) ? $input['search'] : '';
                $search = mb_strtolower($search);
                $searchField = isset($input['search_field']) ? $input['search_field'] : null;
                $questions = ExternalQuestion::where(function ($query) use ($search, $searchField) {
                    if ($searchField === 'formulation') {
                        $query->whereRaw('LOWER(formulation) LIKE BINARY "%'.$search.'%"');
                    } elseif ($searchField === 'answer') {
                        $query->whereRaw('LOWER(answer) LIKE BINARY "%'.$search.'%"');
                    } else {
                        $query->whereRaw('LOWER(formulation) LIKE BINARY "%'.$search.'%"')
                            ->orWhereRaw('LOWER(answer) LIKE BINARY "%'.$search.'%"');
                    }
                });
                if (isset($input['genre'])) {
                    $questions = $questions->where('genre', $input['genre']);
                }
                $questions = $questions->paginate(10);
                $response = $questions;
            } else {
                $response = ExternalQuestion::paginate(10);
            }
            return $this->sendResponse($response, 200);
        }
        return $this->sendError('no_permissions', [], 403);
    }

    public function update(Request $request){
        if (
            Auth::user()->isAdmin() ||
            Auth::user()->hasPermission('quiz_create') ||
            Auth::user()->hasPermission('quiz_edit')
        ) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'id' => 'exists:external_questions',
                'used' => 'required|boolean',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $question = ExternalQuestion::find($input['id']);
            $question->used = $input['used'];
            $question->save();
            return $this->sendResponse($question, 200);
        }
        return $this->sendError('no_permissions', [], 403);
    }
}
