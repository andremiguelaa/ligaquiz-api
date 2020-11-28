<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Request;
use Validator;
use Illuminate\Validation\Rule;
use App\Http\Controllers\BaseController as BaseController;
use App\Traits\GameResults;
use App\Game;
use App\Round;
use App\League;
use App\Season;

class LeagueController extends BaseController
{
    use GameResults;

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

            $query = Game::with('quiz');
            $roundIds = Round::where('season_id', $season->id)->get()->pluck('id')->toArray();
            $query->whereIn('round_id', $roundIds);
            $users = League::where('season_id', $season->id)
                ->where('tier', $input['tier'])
                ->first()
                ->user_ids;
            $query->whereIn('user_id_1', $users)->whereIn('user_id_2', $users);
            $games = $query->get();
            $rounds = $this->getGameResults($games, true);

            $playersIds = League::where('season_id', $season->id)
                ->where('tier', $input['tier'])
                ->first()
                ->user_ids;
            $players = [];
            foreach ($playersIds as $id) {
                $players[$id] = [
                    'id' => $id,
                    'game_points' => 0,
                    'game_points_against' => 0,
                    'wins' => 0,
                    'draws' => 0,
                    'losses' => 0,
                    'forfeits' => 0,
                    'correct_answers' => 0,
                    'league_points' => 0,
                    'played_games' => 0,
                ];
            }
            
            foreach ($rounds as $key => $round) {
                foreach ($round as $game) {
                    if ($game->done && $game->corrected) {
                        if (is_numeric($game->user_id_1_game_points)) {
                            $players[$game->user_id_1]['game_points'] +=
                                $game->user_id_1_game_points;
                        } elseif ($game->user_id_1_game_points === 'F') {
                            $players[$game->user_id_1]['forfeits']++;
                        }
                        $players[$game->user_id_1]['correct_answers'] +=
                                $game->user_id_1_correct_answers;
                        $players[$game->user_id_1]['played_games']++;

                        if (!$game->solo) {
                            $players[$game->user_id_2]['played_games']++;
                            if (is_numeric($game->user_id_2_game_points)) {
                                $players[$game->user_id_2]['game_points'] +=
                                    $game->user_id_2_game_points;
                                $players[$game->user_id_1]['game_points_against'] +=
                                    $game->user_id_2_game_points;
                            } elseif ($game->user_id_2_game_points === 'F') {
                                $players[$game->user_id_2]['forfeits']++;
                            }
                            if (is_numeric($game->user_id_1_game_points)) {
                                $players[$game->user_id_2]['game_points_against'] +=
                                    $game->user_id_1_game_points;
                            }
                            if (
                                is_numeric($game->user_id_1_game_points) &&
                                is_numeric($game->user_id_2_game_points)
                            ) {
                                if ($game->user_id_1_game_points > $game->user_id_2_game_points) {
                                    $players[$game->user_id_1]['wins']++;
                                    $players[$game->user_id_2]['losses']++;
                                    $players[$game->user_id_1]['league_points'] += 3;
                                    $players[$game->user_id_2]['league_points'] += 1;
                                } elseif (
                                    $game->user_id_1_game_points <
                                    $game->user_id_2_game_points
                                ) {
                                    $players[$game->user_id_1]['losses']++;
                                    $players[$game->user_id_2]['wins']++;
                                    $players[$game->user_id_1]['league_points'] += 1;
                                    $players[$game->user_id_2]['league_points'] += 3;
                                } else {
                                    $players[$game->user_id_1]['draws']++;
                                    $players[$game->user_id_2]['draws']++;
                                    $players[$game->user_id_1]['league_points'] += 2;
                                    $players[$game->user_id_2]['league_points'] += 2;
                                }
                            } elseif (
                                is_numeric($game->user_id_1_game_points) &&
                                $game->user_id_2_game_points !== 'P'
                            ) {
                                $players[$game->user_id_1]['wins']++;
                                $players[$game->user_id_1]['league_points'] += 3;
                            } elseif (
                                is_numeric($game->user_id_2_game_points) &&
                                $game->user_id_1_game_points !== 'P'
                            ) {
                                $players[$game->user_id_2]['wins']++;
                                $players[$game->user_id_2]['league_points'] += 3;
                            }
                            $players[$game->user_id_2]['correct_answers'] +=
                                $game->user_id_2_correct_answers;
                        } elseif (is_numeric($game->user_id_1_game_points)) {
                            $players[$game->user_id_1]['league_points'] +=
                                $game->user_id_1_game_points;
                        }
                    }
                }
            }
            usort($players, function ($a, $b) {
                return $this->getTiebreakValue($a) - $this->getTiebreakValue($b);
            });
            foreach ($players as $key => $player) {
                $players[$key]['tiebreak'] = $this->getTiebreakValue($player);
                if ($key > 0 && $players[$key]['tiebreak'] === $players[$key - 1]['tiebreak']) {
                    $players[$key]['rank'] = $players[$key - 1]['rank'];
                } else {
                    $players[$key]['rank'] = $key + 1;
                }
            }
            return $this->sendResponse([
                'ranking' => $players,
                'rounds' => $rounds
            ], 200);
        }
        
        return $this->sendError('no_permissions', [], 403);
    }

    private function getTiebreakValue($player)
    {
        $leaguePoints = sprintf('%02d', $player['league_points'] * 10);
        $gamePointsDifference = sprintf(
            '%03d',
            ($player['game_points'] - $player['game_points_against'] + 500) * 10
        );
        $gamePoints = sprintf('%04d', $player['game_points'] * 10);
        $wins = sprintf('%02d', $player['wins']);
        $correctAnswers = sprintf('%03d', $player['correct_answers']);
        $tiebreak = $leaguePoints . $gamePointsDifference . $gamePoints . $wins . $correctAnswers;
        return 1 / (1 + $tiebreak);
    }
}