<?php

namespace App\Http\Controllers\API;

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
        return $this->sendError('work_in_progress', null, 501);
        /*
        if (Auth::user()->hasPermission('league_create')) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'date' => 'required|array|size:20',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            foreach ($input['date'] as $date) {
                $dateValidator = Validator::make(['date' => $date], [
                    'date' => 'date_format:Y-m-d',
                ]);
                if ($dateValidator->fails()) {
                    return $this->sendError(
                        'validation_error',
                        ['date' => 'validation.format'],
                        400
                    );
                }
            }
            return $this->sendResponse(Season::all(), 200);
        }
        return $this->sendError('no_permissions', [], 403);
        */
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
