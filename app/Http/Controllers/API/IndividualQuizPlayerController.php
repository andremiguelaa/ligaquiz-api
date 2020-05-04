<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use Request;
use Validator;
use App\Http\Controllers\API\BaseController as BaseController;
use App\IndividualQuizPlayer;
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
            if (!count($request::all())) {
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
}
