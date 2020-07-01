<?php

namespace App\Http\Controllers\API;

use App\Rules\Even;
use Illuminate\Support\Facades\Auth;
use Request;
use Validator;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Season;

class SeasonController extends BaseController
{
    public function get()
    {
        if (
            Auth::user()->hasPermission('quiz_play') ||
            Auth::user()->hasPermission('league_create') ||
            Auth::user()->hasPermission('league_edit') ||
            Auth::user()->hasPermission('league_delete')
        ) {
            return $this->sendResponse(Season::all(), 200);
        }
        return $this->sendError('no_permissions', [], 403);
    }

    public function create(Request $request)
    {
        if (Auth::user()->hasPermission('league_create')) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'dates' => 'required|array|size:20',
                'dates.*'  => 'date_format:Y-m-d|distinct|unique:rounds,date',
                'leagues' => 'required|array',
                'leagues.*.tier' => 'required|integer',
                'leagues.*.user_ids' => ['required', 'array', 'max:10', new Even],
                'leagues.*.user_ids.*' => 'exists:users,id|distinct',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            return $this->sendError('work_in_progress', null, 501);
            // return $this->sendResponse(Season::all(), 200);
        }
        return $this->sendError('no_permissions', [], 403);
    }

    public function update(Request $request)
    {
        return $this->sendError('work_in_progress', null, 501);
    }

    public function delete(Request $request)
    {
        return $this->sendError('work_in_progress', null, 501);
    }
}
