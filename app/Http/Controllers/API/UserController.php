<?php

namespace App\Http\Controllers\API;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Http\Controllers\API\BaseController as BaseController;
use Carbon\Carbon;
use App\User;
use App\Http\Resources\User as UserResource;
use App\PasswordReset;
use App\Notifications\PasswordResetRequest;
use Request;
use Validator;
use Avatar;
use Storage;
use Melihovv\Base64ImageDecoder\Base64ImageDecoder;
use Image;

class UserController extends BaseController
{
    public function login(Request $request)
    {
        $validator = Validator::make($request::all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        if ($validator->fails()) {
            return $this->sendError('validation_error', $validator->errors(), 400);
        }
        $credentials = request(['email', 'password']);
        if (!Auth::attempt($credentials)) {
            return $this->sendError('wrong_credentials', [], 401);
        }
        $user = $request::user();
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
        $user = $request::user();
        if (Carbon::parse($user->token()->expires_at)->diffInDays() < 15) {
            $user->token()->revoke();
            $tokenResult = $user->createToken('Personal Access Token');
            $success['access_token'] = $tokenResult->accessToken;
            $success['token_type'] = 'Bearer';
            $tokenResult->token->expires_at = Carbon::now()->addMonth();
            $tokenResult->token->save();
            $success['expires_at'] = Carbon::parse(
                $tokenResult->token->expires_at
            )->toDateTimeString();
        }
        $success['user'] = $user;

        return $this->sendResponse($success);
    }

    public function logout(Request $request)
    {
        $request::user()->token()->revoke();

        return $this->sendResponse();
    }

    public function passwordResetRequest(Request $request)
    {
        $request::validate([
            'email' => 'required|email',
            'language' => 'required|string',
        ]);
        $user = User::where('email', $request::get('email'))->first();
        if (!$user) {
            return $this->sendError('mail_not_found', [], 404);
        }
        $passwordReset = PasswordReset::updateOrCreate(
            ['email' => $user->email],
            [
                'email' => $user->email,
                'token' => Str::random(60),
            ]
        );
        $user->notify((new PasswordResetRequest($passwordReset->token))->locale($request::get('language')));

        return $this->sendResponse(null, 201);
    }

    public function passwordResetConfirm(Request $request)
    {
        $request::validate([
            'password' => 'required|string|min:6|max:255',
            'token' => 'required|string',
        ]);
        $passwordReset = PasswordReset::where('token', $request::get('token'))->first();
        if (!$passwordReset) {
            return $this->sendError('invalid_token', [], 404);
        }
        if (Carbon::parse($passwordReset->updated_at)->addDay()->isPast()) {
            $passwordReset->delete();

            return $this->sendError('expired_token', [], 401);
        }
        $user = User::where('email', $passwordReset->email)->first();
        $user->password = bcrypt($request::get('password'));
        $user->save();
        $user->touch();
        $passwordReset->delete();

        return $this->sendResponse(null, 200);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request::all(), [
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:6|max:255',
        ]);
        if ($validator->fails()) {
            return $this->sendError('validation_error', $validator->errors(), 400);
        }
        $input = $request::all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);
        $avatar = Avatar::create($user->name)->getImageObject()->encode('png');
        $avatar_filename = 'avatar' . $user->id . '.png';
        Storage::put('avatars/' . $avatar_filename, (string) $avatar);
        $user->avatar = $avatar_filename;
        $user->save();

        return $this->sendResponse(null, 201);
    }

    public function get(Request $request)
    {
        if (Auth::user()->hasPermission('user_list')) {
            $partial = false;
            if (!$request::get('id')) {
                $users = User::all();
            } else {
                $user_ids = explode(',', $request::get('id'));
                $users = User::whereIn('id', $user_ids)->get();
                if (count($user_ids) !== $users->count()) {
                    return $this->sendError('users_not_found', [], 400);
                }
                $partial = true;
            }
            if (Auth::user()->hasPermission('user_edit')) {
                return $this->sendResponse($users, $partial ? 206 : 200);
            }

            return $this->sendResponse(UserResource::collection($users), $partial ? 206 : 200);
        }

        return $this->sendError('no_permissions', [], 403);
    }

    public function update(Request $request)
    {
        $input = $request::all();

        if (!Auth::user()->isAdmin() && array_key_exists('roles', $input)) {
            return $this->sendError('no_permissions', [], 403);
        }

        if (Auth::user()->hasPermission('user_edit') || Auth::id() === $input['id']) {
            $validator = Validator::make($input, [
                'id' => 'required|exists:users,id',
                'name' => 'string|max:255',
                'surname' => 'string|max:255',
                'email' => [
                    'email',
                    'max:255',
                    Rule::unique('users')->ignore($input['id']),
                ],
                'password' => 'string|min:6|max:255',
                'roles' => 'array',
                'avatar' => 'base64image|base64max:200',
                'reminders' => 'array',
            ]);
            $validRoles = true;
            if (array_key_exists('roles', $input)) {
                foreach ($input['roles'] as $role) {
                    if (!($role === true || strtotime($role))) {
                        $validRoles = false;
                    }
                }
            }
            if ($validator->fails() || !$validRoles) {
                if (!$validRoles) {
                    $validator->errors()->add('roles', 'validation.roles');
                }
                return $this->sendError('validation_error', $validator->errors(), 400);
            }

            $user = User::find($input['id']);
            if (isset($input['password'])) {
                $input['password'] = bcrypt($input['password']);
            }
            if (isset($input['avatar'])) {
                if ($user->avatar) {
                    Storage::delete('avatars/' . $user->avatar);
                }
                $avatar = new Base64ImageDecoder($input['avatar']);
                $avatar_filename = 'avatar' . $user->id . '.' . $avatar->getFormat();
                Image::make($input['avatar'])->fit(200, 200, function ($constraint) {
                    $constraint->upsize();
                })->save(storage_path('app/public/avatars/' . $avatar_filename));
                $input['avatar'] = $avatar_filename;
            }
            $user->fill($input);
            $user->save();
            $user->touch();
            $success['user'] = $user;

            return $this->sendResponse($success);
        }

        return $this->sendError('no_permissions', [], 403);
    }
}
