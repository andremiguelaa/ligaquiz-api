<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\BaseController as BaseController;
use Carbon\Carbon;

class DateController extends BaseController
{
    public function get()
    {
        return $this->sendResponse(Carbon::now(), 200);
    }
}
