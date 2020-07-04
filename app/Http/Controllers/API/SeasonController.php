<?php

namespace App\Http\Controllers\API;

use Illuminate\Validation\Rule;
use App\Rules\Even;
use Illuminate\Support\Facades\Auth;
use Request;
use Validator;
use Carbon\Carbon;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Season;
use App\League;
use App\Round;
use App\Game;

class SeasonController extends BaseController
{
    public function get(Request $request)
    {
        if (
            Auth::user()->hasPermission('quiz_play') ||
            Auth::user()->hasPermission('league_create') ||
            Auth::user()->hasPermission('league_edit') ||
            Auth::user()->hasPermission('league_delete')
        ) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'season' => 'exists:seasons,season',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            if (isset($input['season'])) {
                $season = Season::with('leagues')
                    ->with('rounds')
                    ->where('season', $input['season'])
                    ->first();
                $season->rounds->makeHidden('season');
                $season->leagues->makeHidden('season');
                $season->leagues->makeHidden('user_ids');
                return $this->sendResponse($season, 200);
            } else {
                return $this->sendResponse(Season::all(), 200);
            }
        }
        return $this->sendError('no_permissions', [], 403);
    }

    public function create(Request $request)
    {
        if (Auth::user()->hasPermission('league_create')) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'dates' => 'required|array|size:20',
                'dates.*'  => 'date_format:Y-m-d|after:today|distinct|unique:rounds,date',
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
            $this->createSeasonLeaguesAndGames($newSeason, $input['leagues']);
            return $this->sendResponse(null, 201);
        }
        return $this->sendError('no_permissions', [], 403);
    }

    public function update(Request $request)
    {
        if (Auth::user()->hasPermission('league_edit')) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'season' => 'required|exists:seasons,season',
                'dates' => 'array|size:20',
                'dates.*'  => [
                    'date_format:Y-m-d',
                    'after:today',
                    'distinct',
                    Rule::unique('rounds', 'date')->ignore($input['season'], 'season'),
                ],
                'leagues' => 'array',
                'leagues.*.tier' => 'required|integer|distinct',
                'leagues.*.user_ids' => ['required', 'array', 'max:10', new Even],
                'leagues.*.user_ids.*' => 'exists:users,id|distinct',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }

            if (isset($input['dates'])) {
                Round::where('season', $input['season'])->delete();
                sort($input['dates']);
                foreach ($input['dates'] as $key => $date) {
                    Round::create([
                        'season' => $input['season'],
                        'round' => $key + 1,
                        'date' => $date,
                    ]);
                }
            }
            if (isset($input['leagues'])) {
                League::where('season', $input['season'])->delete();
                Game::where('season', $input['season'])->delete();
                $this->createSeasonLeaguesAndGames($input['season'], $input['leagues']);
            }
            return $this->sendResponse(null, 201);
        }
        return $this->sendError('no_permissions', [], 403);
    }

    public function delete(Request $request)
    {
        if (Auth::user()->hasPermission('league_delete')) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'season' => 'required|exists:seasons,season',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $firstRound = Round::where('season', $input['season'])->orderBy('date', 'asc')->first();
            $now = Carbon::now()->format('Y-m-d');
            if ($firstRound->date > $now) {
                Season::where('season', $input['season'])->delete();
                Round::where('season', $input['season'])->delete();
                League::where('season', $input['season'])->delete();
                Game::where('season', $input['season'])->delete();
            } else {
                return $this->sendError('past_season', [], 400);
            }
            return $this->sendResponse();
        }
        return $this->sendError('no_permissions', [], 403);
    }

    private function createSeasonLeaguesAndGames($season, $leagues)
    {
        foreach ($leagues as $key => $league) {
            League::create([
                'season' => $season,
                'tier' => $league['tier'],
                'user_ids' => $league['user_ids'],
            ]);
            $numberOfPlayers = count($league['user_ids']);
            for ($round=1; $round <= 20 ; $round++) {
                if ($round === 10 || $round === 20) {
                    for ($game = 1; $game <= $numberOfPlayers; $game++) {
                        Game::create([
                            'season' => $season,
                            'round' => $round,
                            'user_id_1' => $league['user_ids'][$game - 1],
                            'user_id_2' => $league['user_ids'][$game - 1]
                        ]);
                    }
                } else {
                    for ($game = 1; $game <= $numberOfPlayers / 2; $game++) {
                        $pos1 = $game - 1;
                        $pos2 = $numberOfPlayers - ($game-1) - 1;
                        Game::create([
                            'season' => $season,
                            'round' => $round,
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
    }
}
