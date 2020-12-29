<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Request;
use Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Http\Controllers\BaseController as BaseController;
use App\Season;
use App\Round;
use App\Cup;
use App\CupRound;
use App\CupGame;
use App\Cache;

class CupController extends BaseController
{
    public function get(Request $request)
    {
        if (Auth::user()->hasPermission('quiz_play')) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'season' => 'exists:seasons,season',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            if (isset($input['season'])) {
                $season = Season::where('season', $input['season'])->first();
                $cup = Cup::with('rounds.games.cup')
                    ->with('rounds.games.cupRound')
                    ->where('season_id', $season->id)->first();
                if ($cup) {
                    $cup->rounds = $cup->getData();
                    unset($cup->id);
                    unset($cup->season_id);
                    unset($cup->tiebreakers);
                    return $this->sendResponse($cup, 200);
                }
                return $this->sendError('not_found', [], 404);
            }
            return $this->sendResponse(Cup::select('id', 'season_id')->get(), 200);
        }
        return $this->sendError('no_permissions', [], 403);
    }

    public function create(Request $request)
    {
        if (Auth::user()->hasPermission('league_create')) {
            $input = $request::all();
            $totalRounds = 0;
            $minRound = 1;
            if (isset($input['user_ids']) && is_array($input['user_ids'])) {
                $totalRounds = intval(ceil(log(count($input['user_ids']), 2)));
            }
            if (isset($input['season']) && is_int($input['season'])) {
                $season = Season::with('leagues')->where('season', $input['season'])->first();
                if ($season) {
                    $input['season_id'] = $season->id;
                    if (isset($input['rounds']) && is_array($input['rounds'])) {
                        sort($input['rounds']);
                        $rounds = Round::where('season_id', $season->id)
                            ->whereIn('round', $input['rounds'])
                            ->orderBy('round')
                            ->get();
                        $now = Carbon::now()->format('Y-m-d');
                        $minRound = $rounds->where('date', '>', $now)->first();
                        if ($minRound) {
                            $minRound = $minRound->round;
                        } else {
                            $minRound = 21;
                        }
                    }
                }
            }
            $validator = Validator::make($input, [
                'season' => 'required|exists:seasons,season',
                'season_id' => 'unique:cups,season_id',
                'user_ids' => ['required', 'array'],
                'user_ids.*' => 'exists:users,id|distinct',
                'rounds' => ['required', 'array', 'size:'.$totalRounds],
                'rounds.*' => 'distinct|integer|min:'.$minRound.'|max:20',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            foreach ($input['rounds'] as $key => $round) {
                if ($key > 0 && $round - $input['rounds'][$key-1] < 2) {
                    return $this->sendError('validation_error', 'non-consecutive-days', 400);
                }
            }
            $cup = $this->createCup($input, $season, $rounds, $totalRounds);
            return $this->sendResponse($cup, 201);
        }
        return $this->sendError('no_permissions', [], 403);
    }

    public function update(Request $request)
    {
        if (Auth::user()->hasPermission('league_edit')) {
            $input = $request::all();
            if (isset($input['id']) && is_int($input['id'])) {
                $oldCup = Cup::with('rounds.games')->find($input['id']);
                if ($oldCup) {
                    $startDate = Round::find($oldCup->rounds[0]->round_id)->date;
                    $startDate = Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();
                    if ($startDate->lessThanOrEqualTo(Carbon::now())) {
                        return $this->sendError('running_cup', [], 400);
                    }
                }
            }
            $totalRounds = 0;
            $minRound = 1;
            if (isset($input['user_ids']) && is_array($input['user_ids'])) {
                $totalRounds = intval(ceil(log(count($input['user_ids']), 2)));
            }
            if (isset($input['season']) && is_int($input['season'])) {
                $season = Season::with('leagues')->where('season', $input['season'])->first();
                if ($season) {
                    $input['season_id'] = $season->id;
                    if (isset($input['rounds']) && is_array($input['rounds'])) {
                        sort($input['rounds']);
                        $rounds = Round::where('season_id', $season->id)
                            ->whereIn('round', $input['rounds'])
                            ->orderBy('round')
                            ->get();
                        $now = Carbon::now()->format('Y-m-d');
                        $minRound = $rounds->where('date', '>', $now)->first();
                        if ($minRound) {
                            $minRound = $minRound->round;
                        } else {
                            $minRound = 21;
                        }
                    }
                }
            }
            if (!isset($input['id'])) {
                $input['id'] = 0;
            }
            $validator = Validator::make($input, [
                'id' => 'required|exists:cups',
                'season' => 'required|exists:seasons',
                'season_id' => [
                    Rule::unique('cups', 'season_id')->ignore($input['id']),
                ],
                'user_ids' => ['required', 'array'],
                'user_ids.*' => 'exists:users,id|distinct',
                'rounds' => ['required', 'array', 'size:'.$totalRounds],
                'rounds.*' => 'distinct|integer|min:'.$minRound.'|max:20',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            foreach ($input['rounds'] as $key => $round) {
                if ($key > 0 && $round - $input['rounds'][$key-1] < 2) {
                    return $this->sendError('validation_error', 'non-consecutive-days', 400);
                }
            }
            foreach ($oldCup->rounds as $oldRound) {
                foreach ($oldRound->games as $oldGame) {
                    $oldGame->delete();
                }
                $oldRound->delete();
            }
            $cup = $this->createCup($input, $season, $rounds, $totalRounds, $oldCup);
            return $this->sendResponse($cup, 200);
        }
        return $this->sendError('no_permissions', [], 403);
    }

    public function delete(Request $request)
    {
        if (Auth::user()->hasPermission('league_delete')) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'id' => 'required|exists:cups,id',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $cupRounds = CupRound::where('cup_id', $input['id'])->get();
            $roundsIds = $cupRounds->pluck('round_id')->toArray();
            $firstRound = Round::whereIn('id', $roundsIds)->orderBy('date', 'asc')->first();
            $now = Carbon::now()->format('Y-m-d');
            if ($firstRound->date > $now) {
                Cup::where('id', $input['id'])->delete();
                CupRound::where('cup_id', $input['id'])->delete();
                $cupRoundsIds = $cupRounds->pluck('id')->toArray();
                CupGame::whereIn('cup_round_id', $cupRoundsIds)->delete();
            } else {
                return $this->sendError('past_cup', [], 400);
            }
            return $this->sendResponse();
        }
        return $this->sendError('no_permissions', [], 403);
    }

    private function createCup($input, $season, $rounds, $totalRounds, $oldCup = null)
    {
        shuffle($input['user_ids']);
        $tiebreakers = [];
        if ($season->season > 1) {
            $currentSeasonLeagues = $season->leagues;
            $currentSeasonLeaguesWithData = Cache::where('type', 'league')
                ->whereIn('identifier', $currentSeasonLeagues->pluck('id')->toArray())
                ->get();
            foreach ($currentSeasonLeaguesWithData as $league) {
                $tier = $currentSeasonLeagues->find($league->identifier)->tier;
                foreach ($league->value['ranking'] as $player) {
                    if (in_array($player['id'], $input['user_ids'])) {
                        $tiebreakers[$player['id']]['current_tier'] = $tier;
                    }
                }
            }
            $lastSeason = Season::with('leagues')->where('season', $season->season - 1)->first();
            $lastSeasonLeagues = $lastSeason->leagues;
            $lastSeasonLeaguesWithData = Cache::where('type', 'league')
                ->whereIn('identifier', $lastSeasonLeagues->pluck('id')->toArray())
                ->get();
            foreach ($lastSeasonLeaguesWithData as $league) {
                $tier = $lastSeasonLeagues->find($league->identifier)->tier;
                foreach ($league->value['ranking'] as $player) {
                    if (in_array($player['id'], $input['user_ids'])) {
                        $tiebreakers[$player['id']]['last_tier'] = $tier;
                        $tiebreakers[$player['id']]['last_rank'] = $player['rank'];
                    }
                }
            }
        }
        if ($oldCup) {
            $oldCup->season_id = $season->id;
            $oldCup->tiebreakers = $tiebreakers;
            $oldCup->save();
            $cup = $oldCup;
        } else {
            $cup = Cup::create([
                'season_id' => $season->id,
                'tiebreakers' => $tiebreakers
            ]);
        }
        foreach ($input['rounds'] as $key => $round) {
            $cupRound = CupRound::create([
                'cup_id' => $cup->id,
                'cup_round' => $key + 1,
                'round_id' => $rounds[$key]->id
            ]);
            if (!$key) {
                $nextRoundPlayers = pow(2, $totalRounds-$key-1);
                $totalRoundGames = count($input['user_ids']) - $nextRoundPlayers;
                for ($i=0; $i < $totalRoundGames; $i++) {
                    $game = CupGame::create([
                        'cup_round_id' => $cupRound->id,
                        'user_id_1' => $input['user_ids'][$i*2],
                        'user_id_2' => $input['user_ids'][$i*2+1]
                    ]);
                }
                for ($i=$totalRoundGames*2+1; $i <= count($input['user_ids']); $i++) {
                    $game = CupGame::create([
                        'cup_round_id' => $cupRound->id,
                        'user_id_1' => $input['user_ids'][$i-1]
                    ]);
                }
            }
        }
        return $cup;
    }
}
