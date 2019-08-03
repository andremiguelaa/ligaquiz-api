<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Permission;

class PermissionController extends BaseController
{
    public function list()
    {
        if (Auth::user()->isAdmin()) {
            $permissions = array_map(function ($value) {
                return $value['slug'];
            }, Permission::all()->toArray());
            return $this->sendResponse($permissions, 200);
        }
        return $this->sendError('no_permissions', [], 403);
    }
}
