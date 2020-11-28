<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Request;
use Validator;
use Carbon\Carbon;
use App\Http\Controllers\BaseController as BaseController;
use App\Log;
use App\Answer;

class LogController extends BaseController
{
    public function get(Request $request)
    {
        if (Auth::user()->isAdmin()) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'user_id' => 'exists:users,id',
                'search' => 'string',
                'start_date' => 'date_format:Y-m-d',
                'end_date' => 'date_format:Y-m-d|after_or_equal:start_date',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $queryLog = (new Log)->newQuery();
            $queryAnswer = (new Answer)->newQuery();
            if (isset($input['user_id'])) {
                $queryLog = $queryLog->where('user_id', $input['user_id']);
                $queryAnswer = $queryAnswer->where('user_id', $input['user_id']);
            }
            if (isset($input['start_date'])) {
                $queryLog = $queryLog->where('created_at', '>', $input['start_date']);
                $queryAnswer = $queryAnswer->where('created_at', '>', $input['start_date']);
            }
            if (isset($input['end_date'])) {
                $endDate = Carbon::parse($input['end_date'])->addDay();
                $queryLog = $queryLog->where('created_at', '<', $endDate);
                $queryAnswer = $queryAnswer->where('created_at', '<', $endDate);
            }
            if (isset($input['search'])) {
                $queryLog = $queryLog->where('action', 'like', '%'.$input['search'].'%');
                $queryAnswer = $queryAnswer->where('text', 'like', '%'.$input['search'].'%');
            }
            $logs = $queryLog->get();
            $answers = $queryAnswer->get();
            $data = $logs->merge($answers)->sortBy('time')->values();
            
            return $this->sendResponse($data, 200);
        }
        return $this->sendError('no_permissions', [], 403);
    }

    public function create(Request $request)
    {
        $input = $request::all();
        $validator = Validator::make($input, [
            'action' => 'string',
        ]);
        if ($validator->fails()) {
            return $this->sendError('validation_error', $validator->errors(), 400);
        }
        $log = Log::create([
            'user_id' => Auth::id(),
            'action' => $input['action']
        ]);
        return $this->sendResponse(null, 201);
    }
}
