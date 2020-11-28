<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\BaseController as BaseController;
use App\QuestionsTranslations;

class QuestionsTranslationsController extends BaseController
{
    public function get()
    {
        if (Auth::user()->hasPermission('translate')) {
            return $this->sendResponse(QuestionsTranslations::all(), 200);
        }
        return $this->sendError('no_permissions', [], 403);
    }
}
