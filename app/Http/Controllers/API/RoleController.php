<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Role;

class RoleController extends BaseController
{
    public function get()
    {
        if (Auth::user()->isAdmin()) {
            $roles = array_map(function ($value) {
                return $value['slug'];
            }, Role::all()->toArray());

            return $this->sendResponse($roles, 200);
        }

        return $this->sendError('no_permissions', [], 403);
    }
}
