<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\API\BaseController as BaseController;
use Carbon\Carbon;
use App\User;
use Validator;

class UserController extends BaseController
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
            $tokenResult->token->expires_at = Carbon::now()->addWeeks(1);
            $tokenResult->token->save();
        }
        $success['expires_at'] = Carbon::parse(
            $tokenResult->token->expires_at
        )->toDateTimeString();
        return $this->sendResponse($success);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'surname' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:6',
        ]);
        if($validator->fails()){
            return $this->sendError('Validation Error', $validator->errors(), 400);       
        }
        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);
        $success['token'] = $user->createToken('Personal Access Token')->accessToken;
        return $this->sendResponse($success, 201);
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return $this->sendResponse();
    }

    public function details(Request $request)
    {
        return $this->sendResponse($request->user());
    }
}