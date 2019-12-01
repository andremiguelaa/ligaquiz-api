<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Validator;
use App\Http\Controllers\API\BaseController as BaseController;
use App\IndividualQuizPlayer;

class IndividualQuizPlayerController extends BaseController
{
    public function list()
    {
        if (Auth::user()->hasPermission('individual_quiz_player_list')) {
            return $this->sendResponse(IndividualQuizPlayer::all(), 200);
        }

        return $this->sendError('no_permissions', [], 403);
    }

    public function create(Request $request)
    {
        if (Auth::user()->hasPermission('individual_quiz_player_create')) {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'surname' => 'required|string|max:255',
                'user_id' => 'exists:users,id|unique:individual_quiz_players',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $input = $request->all();

            $player = IndividualQuizPlayer::create($input);

            return $this->sendResponse([], 201);
        }

        return $this->sendError('no_permissions', [], 403);
    }
}
