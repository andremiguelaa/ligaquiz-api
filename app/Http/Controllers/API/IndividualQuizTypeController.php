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
            $individualQuizTypes = array_map(function ($value) {
                return $value['slug'];
            }, IndividualQuizType::all()->toArray());

            return $this->sendResponse($individualQuizTypes, 200);
        }

        return $this->sendError('no_permissions', [], 403);
    }
}
