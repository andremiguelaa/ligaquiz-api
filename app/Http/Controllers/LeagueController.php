<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Request;
use Validator;
use Illuminate\Validation\Rule;
use App\Http\Controllers\BaseController as BaseController;
use App\League;
use App\Season;

class LeagueController extends BaseController
{
    public function get(Request $request)
    {
        if (Auth::user()->hasPermission('quiz_play')) {
            $input = $request::all();
            $season = null;
            if (isset($input['season'])) {
                $season = Season::where('season', $input['season'])->first();
            }
            $rules = [
                'season' => 'required|exists:seasons,season',
                'tier' => [
                    'required',
                    'integer',
                    Rule::exists('leagues', 'tier')->where(function ($query) use ($input, $season) {
                        $query->where(
                            'season_id',
                            isset($input['season']) && $season ? $season->id : 0
                        );
                    })
                ]
            ];
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }

            $league = League::where('season_id', $season->id)
                ->where('tier', $input['tier'])
                ->first();

            return $this->sendResponse($league->getData(), 200);
        }
        
        return $this->sendError('no_permissions', [], 403);
    }
}
