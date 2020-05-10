<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use Request;
use Validator;
use App\Http\Controllers\API\BaseController as BaseController;
use App\IndividualQuizPlayer;
use App\IndividualQuizResult;
use App\User;
use App\Http\Resources\User as UserResource;

class IndividualQuizPlayerController extends BaseController
{
    public function get()
    {
        $individualUsers = IndividualQuizPlayer::select(
            'id',
            'name',
            'surname',
            'user_id'
        )->orderBy('name', 'asc')->orderBy('surname', 'asc')->get();

        $individualUsersIds = $individualUsers->pluck('user_id');
        $users = User::whereIn('id', $individualUsersIds)->get();

        return $this->sendResponse(array_map(function ($individualUser) use ($users) {
            $user = $users->find($individualUser['user_id']);
            if ($user) {
                $individualUser['info'] = new UserResource($user);
            }
            unset($individualUser['user_id']);
            return $individualUser;
        }, $individualUsers->toArray()), 200);
    }

    public function create(Request $request)
    {
        if (Auth::user()->hasPermission('individual_quiz_player_create')) {
            $errors = [];
            if (!count($request::all()) || !isset($request::all()[0])) {
                return $this->sendError('validation_error', 'bad_format', 400);
            }
            foreach ($request::all() as $player) {
                $validator = Validator::make($player, [
                    'name' => 'required|string|max:255',
                    'surname' => 'required|string|max:255',
                    'user_id' => 'exists:users,id|unique:individual_quiz_players',
                ]);
                if (count($validator->errors()->getMessages())) {
                    array_push($errors, $validator->errors()->getMessages());
                }
            }
            if (count($errors)) {
                return $this->sendError('validation_error', $errors, 400);
            }
            foreach ($request::all() as $player) {
                IndividualQuizPlayer::create($player);
            }
            return $this->sendResponse(null, 201);
        }

        return $this->sendError('no_permissions', [], 403);
    }

    public function update(Request $request)
    {
        if (Auth::user()->hasPermission('individual_quiz_player_edit')) {
            $errors = [];
            if (!count($request::all()) || !isset($request::all()[0])) {
                return $this->sendError('validation_error', 'bad_format', 400);
            }
            foreach ($request::all() as $player) {
                $user_id_rule = isset($player['id']) ? '|unique:individual_quiz_players,user_id,'.$player['id'] : '';
                $validator = Validator::make($player, [
                    'id' => 'required|exists:individual_quiz_players,id',
                    'name' => 'required|string|max:255',
                    'surname' => 'required|string|max:255',
                    'user_id' => 'exists:users,id'.$user_id_rule,
                ]);
                if (count($validator->errors()->getMessages())) {
                    array_push($errors, $validator->errors()->getMessages());
                }
            }
            if (count($errors)) {
                return $this->sendError('validation_error', $errors, 400);
            }
            foreach ($request::all() as $player) {
                $dbPlayer = IndividualQuizPlayer::find($player['id']);
                $dbPlayer->user_id = null;
                $dbPlayer->fill($player);
                $dbPlayer->save();
            }
            return $this->sendResponse();
        }

        return $this->sendError('no_permissions', [], 403);
    }

    public function delete(Request $request)
    {
        if (Auth::user()->hasPermission('individual_quiz_player_delete')) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'id' => 'required|exists:individual_quiz_players,id',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            if (!IndividualQuizResult::where('individual_quiz_player_id', $input['id'])->count()) {
                IndividualQuizPlayer::find($input['id'])->delete();
            }
            else {
                return $this->sendError('player_with_results', [], 400);
            }
            return $this->sendResponse();
        }

        return $this->sendError('no_permissions', [], 403);
    }
}
