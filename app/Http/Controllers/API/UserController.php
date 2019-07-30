<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use App\Http\Controllers\API\BaseController as BaseController;
use Carbon\Carbon;
use App\User;
use App\Http\Resources\User as UserResource;
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
        if ($validator->fails()) {
            return $this->sendError('validation_error', $validator->errors(), 400);
        }
        $input = $request->all();
        $credentials = request(['email', 'password']);
        if (!Auth::attempt($credentials)) {
            return $this->sendError('wrong_credentials', [], 401);
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
        $success['user'] = $user;
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
        if ($validator->fails()) {
            return $this->sendError('validation_error', $validator->errors(), 400);
        }
        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);
        $success['token'] = $user->createToken('Personal Access Token')->accessToken;
        $avatar = Avatar::create($user->name)->getImageObject()->encode('png');
        Storage::put('avatars/' . $user->id . '/avatar.png', (string) $avatar);
        return $this->sendResponse($success, 201);
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return $this->sendResponse();
    }

    public function list()
    {
        if (Auth::user()->isAdmin() || Auth::user()->hasPermission('users_list')) {
            if (!Input::get('id')) {
                $users = User::all();
            } else {
                $user_ids = explode(',', Input::get('id'));
                $users = User::whereIn('id', $user_ids)->get();
            }
            if (Auth::user()->isAdmin()) {
                return $this->sendResponse($users);
            }
            return $this->sendResponse(UserResource::collection($users));
        }
        return $this->sendError('no_permissions', [], 403);
    }

    public function passwordResetRequest(Request $request)
    {
        $request->validate([
            'email' => 'required',
        ]);
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return $this->sendError('mail_not_found', [], 404);
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
        if (!$passwordReset) {
            return $this->sendError('invalid_token', [], 404);
        }
        if (Carbon::parse($passwordReset->updated_at)->addDay()->isPast()) {
            $passwordReset->delete();
            return $this->sendError('expired_token', [], 401);
        }
        $user = User::where('email', $passwordReset->email)->first();
        if (!$user) {
            return $this->sendError('user_not_found', [], 404);
        }
        $user->password = bcrypt($request->password);
        $user->save();
        $passwordReset->delete();
        return $this->sendResponse(null, 200);
    }
}
