<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\IndividualQuiz;

class NationalRankingController extends BaseController
{
    public function list(Request $request)
    {
        $input = $request->all();
        if (array_key_exists('month', $input)) {
            // TODO: calculate ranking for month
            $response = null;
        } else {
            $response = array_map(function ($value) {
                return substr($value['date'], 0, -3);
            }, IndividualQuiz::select('date')->distinct()->get()->toArray());
        }
        return $this->sendResponse($response, 200);
    }
}
