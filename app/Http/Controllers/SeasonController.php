<?php

namespace App\Http\Controllers;

use Illuminate\Validation\Rule;
use App\Rules\Even;
use Illuminate\Support\Facades\Auth;
use Request;
use Validator;
use Carbon\Carbon;
use App\Http\Controllers\BaseController as BaseController;
use App\Season;
use App\League;
use App\Round;
use App\Game;
use App\Quiz;
use App\Question;
use App\Cache;
use App\Cup;
use App\CupRound;
use App\CupGame;

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
                $season = Season::with(['rounds', 'leagues'])
                    ->where('season', $input['season'])
                    ->orderBy('season', 'desc')
                    ->first();
                $season->rounds->makeHidden('season_id');
                $season->leagues->makeHidden('season_id');
                $quizzes = Quiz::with('questions')
                    ->whereIn('date', $season->rounds->pluck('date')->toArray())
                    ->get();
                $questionIds = [];
                foreach ($quizzes->pluck('questions.*.question_id')->toArray() as $value) {
                    $questionIds = array_merge($questionIds, $value);
                }
                $questions = Question::whereIn('id', $questionIds)->select('genre_id')->get();
                $genreStats = $questions->reduce(function ($acc, $question) {
                    if ($question->genre_id) {
                        if (!isset($acc[$question->genre_id])) {
                            $acc[$question->genre_id] = 0;
                        }
                        $acc[$question->genre_id]++;
                    }
                    return $acc;
                }, []);
                $season->genre_stats = (object) $genreStats;
                return $this->sendResponse($season, 200);
            } else {
                if (array_key_exists('user', $input)) {
                    $validator = Validator::make($input, [
                        'user' => 'exists:users,id'
                    ]);
                    if ($validator->fails()) {
                        return $this->sendError('validation_error', $validator->errors(), 400);
                    }
                    $leagues = Cache::where('type', 'league')->get()->toArray();
                    $userLeagueRanks = array_reduce($leagues, function ($acc, $league) use ($input) {
                        foreach ($league['value']['ranking'] as $user) {
                            if ($user['id'] === intval($input['user'])) {
                                $acc[$league['identifier']] = $user['rank'];
                                break;
                            }
                        }
                        return $acc;
                    }, []);
                    $leagueIds = array_keys($userLeagueRanks);
                    $seasons = Season::with(['rounds', 'leagues'])
                        ->orderBy('season', 'desc')
                        ->get();
                    $seasons = $seasons->map(function ($season) use ($userLeagueRanks, $leagueIds) {
                        $league = $season->leagues->whereIn('id', $leagueIds)->first();
                        if ($league) {
                            $season->user_tier = $league->tier;
                            $season->user_rank = $userLeagueRanks[$league->id];
                        }
                        $season->makeHidden('rounds');
                        $season->makeHidden('leagues');
                        return $season;
                    });
                } else {
                    $seasons = Season::with(['rounds', 'leagues'])
                        ->orderBy('season', 'desc')
                        ->get();
                    $seasons = $seasons->map(function ($season) {
                        $season->rounds->makeHidden('season_id');
                        $season->leagues->makeHidden('season_id');
                        return $season;
                    });
                }
                return $this->sendResponse($seasons, 200);
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
                'leagues' => 'array',
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
                $lastDate = $lastSeason->rounds()->orderBy('date', 'desc')->first()->date;
                if (
                    Carbon::now()->lessThanOrEqualTo(
                        Carbon::createFromFormat('Y-m-d', $lastDate)->endOfDay()
                    )
                ) {
                    return $this->sendError('validation_error', 'still-running-season', 400);
                }
            } else {
                $newSeason = 1;
            }
            $createdSeason = Season::create(['season' => $newSeason]);
            sort($input['dates']);
            $rounds = [];
            foreach ($input['dates'] as $key => $date) {
                $createdRound = Round::create([
                    'season_id' => $createdSeason->id,
                    'round' => $key + 1,
                    'date' => $date,
                ]);
                array_push($rounds, $createdRound);
            }
            $this->createSeasonLeaguesAndGames($createdSeason->id, $input['leagues'], $rounds);
            return $this->sendResponse($createdSeason, 201);
        }
        return $this->sendError('no_permissions', [], 403);
    }

    public function update(Request $request)
    {
        if (Auth::user()->hasPermission('league_edit')) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'id' => 'required|exists:seasons,id',
                'dates' => 'required|array|size:20',
                'dates.*'  => [
                    'date_format:Y-m-d',
                    'after:today',
                    'distinct',
                    Rule::unique('rounds', 'date')->ignore($input['id'], 'season_id'),
                ],
                'leagues' => 'array',
                'leagues.*.tier' => 'required|integer|distinct',
                'leagues.*.user_ids' => ['required', 'array', 'max:10', new Even],
                'leagues.*.user_ids.*' => 'exists:users,id|distinct',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $updatableSeason = Season::orderBy('id', 'desc')->first();
            if ($updatableSeason && $input['id'] !== $updatableSeason->id) {
                return $this->sendError('validation_error', 'non-updateable-season', 400);
            }

            $oldRounds = Round::where('season_id', $input['id'])->get();
            $oldRoundsIds = $oldRounds->pluck('id')->toArray();
            Round::where('season_id', $input['id'])->delete();
            sort($input['dates']);
            $rounds = [];
            foreach ($input['dates'] as $key => $date) {
                $createdRound = Round::create([
                    'season_id' => $input['id'],
                    'round' => $key + 1,
                    'date' => $date,
                ]);
                array_push($rounds, $createdRound);
            }
            if (isset($input['leagues'])) {
                League::where('season_id', $input['id'])->delete();
                Game::whereIn('round_id', $oldRoundsIds)->delete();
                $this->createSeasonLeaguesAndGames($input['id'], $input['leagues'], $rounds);
                $cup = Cup::with('rounds')->where('season_id', $input['id'])->first();
                if ($cup) {
                    $oldRoundsGrouped = $oldRounds->groupBy('id');
                    $newRoundsGrouped = Round::where('season_id', $input['id'])->get()
                        ->groupBy('round');
                    $cupRounds = $cup->rounds()->get();

                    $tiebreakers = [];
                    $cupPlayersIds = array_keys($cup->tiebreakers);
                    if ($updatableSeason->season > 1) {
                        $currentSeasonLeagues = $updatableSeason->leagues;
                        foreach ($currentSeasonLeagues as $league) {
                            $tier = $league->tier;
                            $players = $league->user_ids;
                            foreach ($players as $player) {
                                $tiebreakers[$player]['current_tier'] = $tier;
                            }
                        }
                        $lastSeason = Season::with('leagues')
                            ->where('season', $updatableSeason->season - 1)
                            ->first();
                        $lastSeasonLeagues = $lastSeason->leagues;
                        $lastSeasonLeaguesWithData = Cache::where('type', 'league')
                            ->whereIn('identifier', $lastSeasonLeagues->pluck('id')->toArray())
                            ->get();
                        foreach ($lastSeasonLeaguesWithData as $league) {
                            $tier = $lastSeasonLeagues->find($league->identifier)->tier;
                            foreach ($league->value['ranking'] as $player) {
                                if (in_array($player['id'], $cupPlayersIds)) {
                                    $tiebreakers[$player['id']]['last_tier'] = $tier;
                                    $tiebreakers[$player['id']]['last_rank'] = $player['rank'];
                                }
                            }
                        }
                    }
                    $cup->tiebreakers = $tiebreakers;
                    $cup->save();

                    foreach ($cupRounds as $cupRound) {
                        $oldSeasonRound = $oldRoundsGrouped[$cupRound->round_id][0]['round'];
                        $newRoundId = $newRoundsGrouped[$oldSeasonRound][0]['id'];
                        $cupRound->round_id = $newRoundId;
                        $cupRound->save();
                    }
                }
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
                'id' => 'required|exists:seasons,id',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $firstRound = Round::where('season_id', $input['id'])
                ->orderBy('date', 'asc')->first();
            $now = Carbon::now()->format('Y-m-d');
            if ($firstRound->date > $now) {
                Season::where('id', $input['id'])->delete();
                $oldRoundsIds = Round::where('season_id', $input['id'])
                    ->get()
                    ->pluck('id')
                    ->toArray();
                Round::where('season_id', $input['id'])->delete();
                League::where('season_id', $input['id'])->delete();
                Game::whereIn('round_id', $oldRoundsIds)->delete();
                $cup = Cup::where('season_id', $input['id'])->first();
                if ($cup) {
                    $cupRounds = CupRound::where('cup_id', $cup->id)->get();
                    $roundsIds = $cupRounds->pluck('round_id')->toArray();
                    $cup->delete();
                    CupRound::where('cup_id', $cup->id)->delete();
                    $cupRoundsIds = $cupRounds->pluck('id')->toArray();
                    CupGame::whereIn('cup_round_id', $cupRoundsIds)->delete();
                }
            } else {
                return $this->sendError('past_season', [], 400);
            }
            return $this->sendResponse();
        }
        return $this->sendError('no_permissions', [], 403);
    }

    private function createSeasonLeaguesAndGames($seasonId, $leagues, $rounds)
    {
        foreach ($leagues as $key => $league) {
            League::create([
                'season_id' => $seasonId,
                'tier' => $league['tier'],
                'user_ids' => $league['user_ids'],
            ]);
            $numberOfPlayers = count($league['user_ids']);
            foreach ($rounds as $round) {
                if ($round->round === 10 || $round->round === 20) {
                    for ($game = 1; $game <= $numberOfPlayers; $game++) {
                        Game::create([
                            'round_id' => $round->id,
                            'user_id_1' => $league['user_ids'][$game - 1],
                            'user_id_2' => $league['user_ids'][$game - 1]
                        ]);
                    }
                } else {
                    for ($game = 1; $game <= $numberOfPlayers / 2; $game++) {
                        $pos1 = $game - 1;
                        $pos2 = $numberOfPlayers - ($game-1) - 1;
                        Game::create([
                            'round_id' => $round->id,
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
