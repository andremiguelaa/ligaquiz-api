<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller as Controller;
use App\Role;
use App\Permission;

class BaseController extends Controller
{
    public function sendResponse($result = null, $code = 200)
    {
        $response = [
            'success' => true,
        ];
        if ($result) {
            $response['data'] = $result;
        }
        return response()->json($response, $code);
    }

    public function sendError($error, $errorMessages = [], $code = 404)
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];

        if (!empty($errorMessages)) {
            $response['data'] = $errorMessages;
        }

        return response()->json($response, $code);
    }

    public function defaults()
    {
        $admin = Auth::user()->isAdmin();
        $defaults = [];

        if ($admin) {
            $permissions = array_map(function ($value) {
                return $value['slug'];
            }, Permission::all()->toArray());
            $defaults['permissions'] = $permissions;
        }

        if ($admin || Auth::user()->hasPermission('user_create') || Auth::user()->hasPermission('user_edit')) {
            $roles = array_map(function ($value) {
                return $value['slug'];
            }, Role::all()->toArray());
            $defaults['roles'] = $roles;
        }

        return response()->json((object) $defaults);
    }
}
