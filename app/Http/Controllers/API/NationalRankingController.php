<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use Request;
use Validator;
use App\Http\Controllers\API\BaseController as BaseController;
use App\IndividualQuiz;
use App\NationalRanking;

class NationalRankingController extends BaseController
{
    public function get(Request $request)
    {
        $input = $request::all();
        if (array_key_exists('month', $input)) {
            $ranking = NationalRanking::where('date', $input['month'].'-01')->first();
            if (!$ranking) {
                return $this->sendError('not_found', [], 404);
            }
            $response = $ranking->getData();
        } else {
            $response = array_map(function ($ranking) {
                return substr($ranking['date'], 0, -3);
            }, NationalRanking::orderBy('date', 'desc')->get()->toArray());
        }
        return $this->sendResponse($response, 200);
    }

    public function create(Request $request)
    {
        if (Auth::user()->hasPermission('national_ranking_create')) {
            $input = $request::all();
            if (array_key_exists('month', $input)) {
                $input['month'] = $input['month'].'-01';
            }
            $validator = Validator::make($input, [
                'month' => 'required|date_format:Y-m-d|unique:national_rankings,date',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $input['date'] = $input['month'];
            NationalRanking::create($input);
            return $this->sendResponse(null, 201);
        }

        return $this->sendError('no_permissions', [], 403);
    }

    public function delete(Request $request)
    {
        if (Auth::user()->hasPermission('national_ranking_delete')) {
            $input = $request::all();
            if (array_key_exists('month', $input)) {
                $input['month'] = $input['month'].'-01';
            }
            $validator = Validator::make($input, [
                'month' => 'required|exists:national_rankings,date',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            NationalRanking::where('date', $input['month'])->delete();
            return $this->sendResponse();
        }

        return $this->sendError('no_permissions', [], 403);
    }
}
