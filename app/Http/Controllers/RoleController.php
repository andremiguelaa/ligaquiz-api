<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\BaseController as BaseController;
use App\Role;

class RoleController extends BaseController
{
    public function get()
    {
        if (Auth::user()->isAdmin()) {
            $roles = Role::all()->map(function ($role) {
                return $role['slug'];
            });
            return $this->sendResponse($roles, 200);
        }

        return $this->sendError('no_permissions', [], 403);
    }
}
