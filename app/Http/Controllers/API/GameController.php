<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use Request;
use Validator;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Game;

class GameController extends BaseController
{
    public function get(Request $request)
    {
        if (Auth::user()->hasPermission('quiz_play')) {
            $input = $request::all();
            $rules = [
                'id' => 'exists:games,id',
                'season' => 'required_with:round|exists:seasons,season',
                'round' => 'integer|between:1,20',
                'user' => 'exists:users,id'
            ];
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $query = (new Game)->newQuery();
            foreach ($input as $key => $value) {
                if (in_array($key, array_keys($rules))) {
                    if ($key === 'user') {
                        $query->where(function ($userQuery) use ($value) {
                            $userQuery->where('user_id_1', $value)->orWhere('user_id_2', $value);
                        });
                    } else {
                        $query->where($key, $value);
                    }
                }
            }
            return $this->sendResponse($query->get(), 200);
        }
        
        return $this->sendError('no_permissions', [], 403);
    }
}
