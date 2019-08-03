<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller as Controller;

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
}
