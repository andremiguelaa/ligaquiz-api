<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use Request;
use Validator;
use Illuminate\Validation\Rule;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Traits\GameResults;
use App\League;

class LeagueController extends BaseController
{
    use GameResults;

    public function get(Request $request)
    {
        if (Auth::user()->hasPermission('quiz_play')) {
            $input = $request::all();
            $rules = [
                'season' => 'required|exists:seasons,season',
                'tier' => [
                    'required',
                    'integer',
                    Rule::exists('leagues', 'tier')->where(function ($query) use ($input) {
                        $query->where('season', isset($input['season']) ? $input['season'] : 0);
                    })
                ]
            ];
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $playersIds = League::where('season', $input['season'])
                ->where('tier', $input['tier'])
                ->first()
                ->user_ids;
            $players = array_map(function ($id) {
                return [
                    'id' => $id,
                    'league_points' => 0,
                    'game_points' => 0,
                    'game_points_against' => 0,
                    'wins' => 0,
                    'draws' => 0,
                    'losses' => 0,
                    'forfeits' => 0,
                    'correct_answers' => 0

                ];
            }, $playersIds);
            // $roundResults = $this->getGameResults($input, $rules);
            return $this->sendError('work_in_progress', null, 501);
            //return $this->sendResponse($players, 200);
        }
        
        return $this->sendError('no_permissions', [], 403);
    }
}
