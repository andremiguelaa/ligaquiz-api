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
        $avatar = Avatar::create($user->name)->getImageObject()->encode('png');
        Storage::put('avatars/'.$user->id.'/avatar.png', (string) $avatar);
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

    public function passwordResetRequest(Request $request)
    {
        $request->validate([
            'email' => 'required',
        ]);
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return $this->sendError('Mail not found', [], 404);
        }
        $passwordReset = PasswordReset::updateOrCreate(
            ['email' => $user->email],
            [
                'email' => $user->email,
                'token' => str_random(60)
            ]
        );
        $user->notify(new PasswordResetRequest($passwordReset->token));
        return $this->sendResponse(null, 201);
    }

    public function passwordResetConfirm(Request $request)
    {
        $request->validate([
            'password' => 'required|min:6',
            'token' => 'required'
        ]);
        $passwordReset = PasswordReset::where('token', $request->token)->first();
        if (!$passwordReset){
            return $this->sendError('Invalid token', [], 404);
        }
        if (Carbon::parse($passwordReset->updated_at)->addDay()->isPast()) {
            $passwordReset->delete();
            return $this->sendError('Expired token', [], 401);
        }
        $user = User::where('email', $passwordReset->email)->first();
        if (!$user){
            return $this->sendError('User not found', [], 404);
        }
        $user->password = bcrypt($request->password);
        $user->save();
        $passwordReset->delete();
        return $this->sendResponse(null, 200);
    }
    
}