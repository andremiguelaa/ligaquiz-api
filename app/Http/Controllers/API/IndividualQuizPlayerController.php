<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
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
}
