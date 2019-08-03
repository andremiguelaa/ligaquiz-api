<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\API\BaseController as BaseController;
use App\IndividualQuizType;

class IndividualQuizTypeController extends BaseController
{
    public function list()
    {
        if (Auth::user()->hasPermission('individual_quiz_type_list')) {
            return $this->sendResponse(IndividualQuizType::all(), 200);
        }
        return $this->sendError('no_permissions', [], 403);
    }
}
