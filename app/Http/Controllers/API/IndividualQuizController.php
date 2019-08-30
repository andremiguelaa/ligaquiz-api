<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\API\BaseController as BaseController;
use App\IndividualQuiz;

class IndividualQuizController extends BaseController
{
    public function list()
    {
        if (Auth::user()->hasPermission('individual_quiz_list')) {
            return $this->sendResponse(IndividualQuiz::all(), 200);
        }

        return $this->sendError('no_permissions', [], 403);
    }
}
