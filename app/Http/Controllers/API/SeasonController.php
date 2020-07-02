<?php

namespace App\Http\Controllers\API;

use App\Rules\Even;
use Illuminate\Support\Facades\Auth;
use Request;
use Validator;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Season;
use App\League;
use App\Round;
use App\Game;

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
                'leagues.*.tier' => 'required|integer|distinct',
                'leagues.*.user_ids' => ['required', 'array', 'max:10', new Even],
                'leagues.*.user_ids.*' => 'exists:users,id|distinct',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }

            $lastSeason = Season::orderBy('season', 'desc')->first();
            if ($lastSeason) {
                $newSeason = $lastSeason->season + 1;
            } else {
                $newSeason = 1;
            }
            sort($input['dates']);
            foreach ($input['dates'] as $key => $date) {
                Round::create([
                    'season' => $newSeason,
                    'round' => $key + 1,
                    'date' => $date,
                ]);
            }
            Season::create(['season' => $newSeason]);
            foreach ($input['leagues'] as $key => $league) {
                League::create([
                    'season' => $newSeason,
                    'tier' => $league['tier'],
                    'user_ids' => $league['user_ids'],
                ]);
                $numberOfPlayers = count($league['user_ids']);
                foreach ($input['dates'] as $key => $date) {
                    if ($key === 9 || $key === 19) {
                        for ($game = 1; $game <= $numberOfPlayers; $game++) {
                            Game::create([
                                'season' => $newSeason,
                                'round' => $key + 1,
                                'user_id_1' => $league['user_ids'][$game - 1],
                                'user_id_2' => $league['user_ids'][$game - 1]
                            ]);
                        }
                    } else {
                        for ($game = 1; $game <= $numberOfPlayers / 2; $game++) {
                            $pos1 = $game - 1;
                            $pos2 = $numberOfPlayers - ($game-1) - 1;
                            Game::create([
                                'season' => $newSeason,
                                'round' => $key + 1,
                                'user_id_1' => $league['user_ids'][$pos1],
                                'user_id_2' => $league['user_ids'][$pos2]
                            ]);
                        }
                        // rotate players array
                        $playersTemp = $league['user_ids'];
                        $top = array_shift($playersTemp);
                        $last = array_pop($playersTemp);
                        $league['user_ids'] = [$last];
                        foreach ($playersTemp as $value) {
                            $league['user_ids'][] = $value;
                        }
                        array_unshift($league['user_ids'], $top);
                    }
                }
            }
            return $this->sendResponse(null, 201);
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
