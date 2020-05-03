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
        $individualUsers = IndividualQuizPlayer::all(
            'id',
            'name',
            'surname',
            'user_id'
        );

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
            $validator = Validator::make($request::all(), [
                'name' => 'required|string|max:255',
                'surname' => 'required|string|max:255',
                'user_id' => 'exists:users,id|unique:individual_quiz_players',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $input = $request::all();

            IndividualQuizPlayer::create($input);

            return $this->sendResponse(null, 201);
        }

        return $this->sendError('no_permissions', [], 403);
    }
}
