<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use Request;
use Validator;
use Illuminate\Validation\Rule;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Game;
use App\Round;
use App\League;
use App\Traits\GameResults;

class GameController extends BaseController
{
    use GameResults;

    public function get(Request $request)
    {
        if (Auth::user()->hasPermission('quiz_play')) {
            $input = $request::all();
            $rules = [
                'id' => 'exists:games,id|required_without_all:season_id,user',
                'season_id' => 'required_with:tier|exists:seasons,id|required_without_all:id,user',
                'tier' => [
                    'required_with:season_id',
                    'integer',
                    Rule::exists('leagues', 'tier')->where(function ($query) use ($input) {
                        $query->where(
                            'season_id',
                            isset($input['season_id']) ? $input['season_id'] : 0
                        );
                    })
                ],
                'date' => 'exists:rounds,date',
                'user' => [
                    'exists:users,id',
                    'required_without_all:id,season_id',
                    'required_with:opponent',
                    'required_with:date'
                ],
                'page' => 'required_without_all:id,date|integer|min:1',
                'opponent' => 'exists:users,id|required_with:date',
            ];
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $query = Game::with('quiz');
            if (isset($input['id'])) {
                $query->where('id', $input['id']);
            }
            if (isset($input['date'])) {
                $round = Round::where('date', $input['date'])->first();
                $query->where('round_id', $round->id);
                $query->where('user_id_1', $input['user']);
                $query->where('user_id_2', $input['opponent']);
            }
            if (isset($input['season_id']) && isset($input['tier'])) {
                $tier = true;
                $roundIds = Round::where('season_id', $input['season_id'])
                    ->get()
                    ->pluck('id')
                    ->toArray();
                $query->whereIn('round_id', $roundIds);
                $users = League::where('season_id', $input['season_id'])
                    ->where('tier', $input['tier'])
                    ->first()
                    ->user_ids;
                $query->whereIn('user_id_1', $users)->whereIn('user_id_2', $users);
            } else {
                $tier = false;
            }
            if (isset($input['user']) && !isset($input['date'])) {
                if (isset($input['opponent'])) {
                    $query->where(function ($userQuery) use ($input) {
                        $userQuery
                            ->where('user_id_1', $input['user'])
                            ->where('user_id_2', $input['opponent']);
                    })->orWhere(function ($userQuery) use ($input) {
                        $userQuery
                            ->where('user_id_1', $input['opponent'])
                            ->where('user_id_2', $input['user']);
                    });
                } else {
                    $query->where(function ($userQuery) use ($input) {
                        $userQuery
                            ->where('user_id_1', $input['user'])
                            ->orWhere('user_id_2', $input['user']);
                    });
                }
            }
            if (
                isset($input['page']) &&
                !isset($input['season_id']) &&
                !isset($input['tier']) &&
                !isset($input['id'])
            ) {
                $paginator = $query->paginate(20);
            }
            $games = $query->get();
            $gameResults = $this->getGameResults($games, $tier);
            if (isset($input['id'])) {
                $response = $gameResults;
            } else {
                $response = [
                    'results' => $gameResults,
                ];
                if (isset($paginator)) {
                    $response['total'] = $paginator->total();
                    $response['last_page'] = $paginator->lastPage();
                }
            }
            return $this->sendResponse($response, 200);
        }
        
        return $this->sendError('no_permissions', [], 403);
    }
}
