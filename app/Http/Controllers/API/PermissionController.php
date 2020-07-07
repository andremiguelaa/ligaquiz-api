<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Permission;

class PermissionController extends BaseController
{
    public function get()
    {
        if (Auth::user()->isAdmin()) {
            $permissions = Permission::all()->map(function ($permission) {
                return $permission['slug'];
            });
            return $this->sendResponse($permissions, 200);
        }

        return $this->sendError('no_permissions', [], 403);
    }
}
