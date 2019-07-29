<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\API\BaseController as BaseController;
use Carbon\Carbon;
use App\User;
use App\PasswordReset;
use App\Notifications\PasswordResetRequest;
use Validator;
use Avatar;
use Storage;

class SessionController extends BaseController
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',
        ]);
        if($validator->fails()){
            return $this->sendError('Validation Error', $validator->errors(), 400);
        }
        $input = $request->all();
        $credentials = request(['email', 'password']);
        if(!Auth::attempt($credentials)){
            return $this->sendError('Wrong credentials', [] , 401);
        }
        $user = $request->user();
        $tokenResult = $user->createToken('Personal Access Token');
        $success['access_token'] = $tokenResult->accessToken;
        $success['token_type'] = 'Bearer';
        if ($request->remember_me) {
            $tokenResult->token->expires_at = Carbon::now()->addMonth();
            $tokenResult->token->save();
        }
        $success['expires_at'] = Carbon::parse(
            $tokenResult->token->expires_at
        )->toDateTimeString();
        return $this->sendResponse($success);
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return $this->sendResponse();
    }
    
}