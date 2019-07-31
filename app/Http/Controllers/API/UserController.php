<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
        $credentials = request(['email', 'password']);
        if (!Auth::attempt($credentials)) {
            return $this->sendError('wrong_credentials', [], 401);
        }
        $user = $request->user();
        $tokenResult = $user->createToken('Personal Access Token');
        $success['access_token'] = $tokenResult->accessToken;
        $success['token_type'] = 'Bearer';
        $tokenResult->token->expires_at = Carbon::now()->addMonth();
        $tokenResult->token->save();
        $success['expires_at'] = Carbon::parse(
            $tokenResult->token->expires_at
        )->toDateTimeString();
        $success['user'] = $user;
        return $this->sendResponse($success);
    }

    public function renew(Request $request)
    {
        $user = $request->user();
        $user->token()->revoke();
        $tokenResult = $user->createToken('Personal Access Token');
        $success['access_token'] = $tokenResult->accessToken;
        $success['token_type'] = 'Bearer';
        $tokenResult->token->expires_at = Carbon::now()->addMonth();
        $tokenResult->token->save();
        $success['expires_at'] = Carbon::parse(
            $tokenResult->token->expires_at
        )->toDateTimeString();
        $success['user'] = $user;
        return $this->sendResponse($success);
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return $this->sendResponse();
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
        $avatar = Avatar::create($user->name)->getImageObject()->encode('png');
        Storage::put('avatars/' . $user->id . '/avatar.png', (string) $avatar);
        return $this->sendResponse([], 201);
    }

    public function list()
    {
        if (Auth::user()->isAdmin() || Auth::user()->hasPermission('users_list')) {
            $partial = false;
            if (!Input::get('id')) {
                $users = User::all();
            } else {
                $user_ids = explode(',', Input::get('id'));
                $users = User::whereIn('id', $user_ids)->get();
                if (count($user_ids) !== $users->count()) {
                    return $this->sendError('users_not_found', [], 400);
                }
                $partial = true;
            }
            if (Auth::user()->isAdmin()) {
                return $this->sendResponse($users, $partial ? 206 : 200);
            }
            return $this->sendResponse(UserResource::collection($users), $partial ? 206 : 200);
        }
        return $this->sendError('no_permissions', [], 403);
    }

    public function update(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:users,id',
            'name' => 'max:255',
            'surname' => 'max:255',
            'email' => [
                'email',
                'max:255',
                Rule::unique('users')->ignore($input['id']),
            ],
            'password' => 'min:6',
            'roles' => 'json',
            'avatar' => 'string',
            'subscription' => 'date',
            'reminders' => 'json',
        ]);
        if ($validator->fails()) {
            return $this->sendError('validation_error', $validator->errors(), 400);
        }
        $user = User::find($input['id']);
        if (isset($input['password'])) {
            $input['password'] = bcrypt($input['password']);
        }
        // Todo: avatar upload
        if (Auth::user()->isAdmin()) {
            $user->fill($input);
            $user->save();
            return $this->sendResponse();
        }
        if (Auth::id() === $input['id']) {
            unset($input['roles'], $input['subscription']);
            $user->fill($input);
            $user->save();
            return $this->sendResponse();
        }
        return $this->sendError('no_permissions', [], 403);
    }
}
