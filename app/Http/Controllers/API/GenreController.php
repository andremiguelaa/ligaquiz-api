<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Genre;

class GenreController extends BaseController
{
    public function get()
    {
        return $this->sendResponse(Genre::all(), 200);
    }
}
