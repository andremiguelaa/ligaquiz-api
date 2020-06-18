<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;

class LeagueController extends BaseController
{
    public function get()
    {
        return $this->sendError('work_in_progress', null, 501);
    }
}