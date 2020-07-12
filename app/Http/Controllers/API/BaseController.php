<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller as Controller;

class BaseController extends Controller
{
    public function sendResponse($response = null, $code = 200)
    {
        return response()->json($response, $code);
    }

    public function sendError($error, $errorMessages = [], $code = 404)
    {
        $response = [
            'message' => $error,
        ];

        if (!empty($errorMessages)) {
            $response['data'] = $errorMessages;
        }

        return response()->json($response, $code);
    }
}
