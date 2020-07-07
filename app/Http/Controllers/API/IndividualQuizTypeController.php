<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\IndividualQuizType;

class IndividualQuizTypeController extends BaseController
{
    public function get()
    {
        $individualQuizTypes = IndividualQuizType::all()->map(function ($type) {
            return $type['slug'];
        });
        return $this->sendResponse($individualQuizTypes, 200);
    }
}
