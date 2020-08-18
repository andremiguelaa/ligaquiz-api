<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use Request;
use Validator;
use Carbon\Carbon;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Log;

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
            $query = (new Log)->newQuery();
            if (isset($input['user_id'])) {
                $query = $query->where('user_id', $input['user_id']);
            }
            if (isset($input['start_date'])) {
                $query = $query->where('created_at', '>', $input['start_date']);
            }
            if (isset($input['end_date'])) {
                $endDate = Carbon::parse($input['end_date'])->addDay();
                $query = $query->where('created_at', '<', $endDate);
            }
            if (isset($input['search'])) {
                $query = $query->where('action', 'like', '%'.$input['search'].'%');
            }
            return $this->sendResponse($query->get(), 200);
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
