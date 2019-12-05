<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\IndividualQuizType;

class IndividualQuizTypeController extends BaseController
{
    public function list()
    {
        $individualQuizTypes = array_map(function ($value) {
            return $value['slug'];
        }, IndividualQuizType::all()->toArray());

        return $this->sendResponse($individualQuizTypes, 200);
    }
}
