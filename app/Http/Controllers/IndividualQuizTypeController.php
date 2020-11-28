<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController as BaseController;
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
