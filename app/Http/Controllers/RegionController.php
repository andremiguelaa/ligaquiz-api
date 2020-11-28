<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\BaseController as BaseController;
use App\Region;

class RegionController extends BaseController
{
    public function get()
    {
        $regions = Region::all()->map(function ($region) {
            return $region['code'];
        });
        return $this->sendResponse($regions, 200);
    }
}
