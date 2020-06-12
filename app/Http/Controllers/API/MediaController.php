<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Media;

class MediaController extends BaseController
{
    public function get()
    {
        if (
            Auth::user()->hasPermission('quiz_create') ||
            Auth::user()->hasPermission('quiz_edit') ||
            Auth::user()->hasPermission('specialquiz_create') ||
            Auth::user()->hasPermission('specialquiz_edit')
        ) {
            return $this->sendResponse(Media::all(), 200);
        }
        return $this->sendError('no_permissions', [], 403);
    }
}
