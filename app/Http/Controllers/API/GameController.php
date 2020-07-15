<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use Request;
use Validator;
use Illuminate\Validation\Rule;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Traits\GameResults;

class GameController extends BaseController
{
    use GameResults;

    public function get(Request $request)
    {
        if (Auth::user()->hasPermission('quiz_play')) {
            $input = $request::all();
            $rules = [
                'id' => 'exists:games,id|required_without_all:season,user',
                'season_id' => 'required_with:tier|exists:seasons,id|required_without_all:id,user',
                'tier' => [
                    'required_with:season',
                    'integer',
                    Rule::exists('leagues', 'tier')->where(function ($query) use ($input) {
                        $query->where('season', isset($input['season']) ? $input['season'] : 0);
                    })
                ],
                'user' => 'exists:users,id|required_without_all:id,season|required_with:opponent',
                'opponent' => 'exists:users,id'
            ];
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            return $this->sendResponse($this->getGameResults($input, $rules), 200);
        }
        
        return $this->sendError('no_permissions', [], 403);
    }
}
