<?php

namespace App\Http\Controllers;

use Illuminate\Validation\Rule;
use App\Rules\RoleValue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Http\Controllers\BaseController as BaseController;
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
use App\Question;
use App\QuizQuestion;
use App\Answer;
use App\NationalRanking;
use App\Mail\NewUser;

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
        if (!Auth::guard('web')->attempt($credentials)) {
            return $this->sendError('wrong_credentials', [], 401);
        }
        $user = Auth::guard('web')->user();
        $tokenResult = $user->createToken('Personal Access Token');
        $success['access_token'] = $tokenResult->accessToken;
        $success['token_type'] = 'Bearer';
        $tokenResult->token->expires_at = Carbon::now()->addMonth();
        $tokenResult->token->save();
        $success['expires_at'] = Carbon::parse(
            $tokenResult->token->expires_at
        )->toDateTimeString();
        $user = $user->toArray();
        $user['avatar'] = $user['avatar_url'];
        unset($user['avatar_url']);
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
        $user = $user->toArray();
        $user['avatar'] = $user['avatar_url'];
        unset($user['avatar_url']);
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
            'birthday' => 'nullable|date_format:Y-m-d|before:today',
            'region' => 'nullable|exists:regions,code',
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
        $user->reminders = [
            'quiz' => [
                'daily' => true,
                'deadline' => true
            ],
            'special_quiz' => [
                'daily' => true,
                'deadline' => true
            ]
        ];
        $user->save();

        $possibleAdmins = User::where('roles', 'like', '%admin%')->get();
        $adminEmails = $possibleAdmins->reduce(function ($carry, $item) {
            if ($item->isAdmin()) {
                array_push($carry, $item->email);
            }
            return $carry;
        }, []);
        Mail::bcc($adminEmails)
            ->locale(isset($input['language']) ? $input['language'] : 'en')
            ->send(new NewUser($user));

        return $this->sendResponse(null, 201);
    }

    public function get(Request $request)
    {
        $input = $request::all();
        $validator = Validator::make($input, [
            'id' => 'array',
            'id.*' => 'exists:users,id'
        ]);
        if ($validator->fails()) {
            return $this->sendError('validation_error', $validator->errors(), 400);
        }
        $partial = false;
        if (!isset($input['id'])) {
            $users = User::with('individual_quiz_player')->get();
        } else {
            if (count($input['id']) > 2 && isset($input['statistics'])) {
                return $this->sendError(
                    'validation_error',
                    ['id' => ['statistics_max_exceeded']],
                    400
                );
            }
            $partial = true;
            $users = User::with('individual_quiz_player')
                ->whereIn('id', $input['id'])
                ->get();
            if (count($input['id']) <= 2 && isset($input['statistics'])) {
                $statistics = [];
                foreach ($input['id'] as $userId) {
                    $statistics[$userId] = [];
                }
                $startOfDay = Carbon::now()->startOfDay();
                $answers = Answer::whereIn('user_id', $input['id'])
                    ->where('submitted', 1)
                    ->where('corrected', 1)
                    ->where('created_at', '<', $startOfDay)
                    ->select('user_id', 'question_id', 'correct')
                    ->get();
                $questionIds = $answers->pluck('question_id');
                $questions = Question::whereIn('id', $questionIds)
                    ->whereNotNull('genre_id')
                    ->select('id', 'genre_id')
                    ->get()
                    ->groupBy('id')
                    ->toArray();
                foreach ($answers as $answer) {
                    if (isset($questions[$answer->question_id])) {
                        $question = $questions[$answer->question_id][0];
                        $genreId = $question['genre_id'];
                        if ($genreId) {
                            if (!isset($statistics[$answer->user_id][$genreId])) {
                                $statistics[$answer->user_id][$genreId] = [
                                    'total' => 0,
                                    'correct' => 0
                                ];
                            }
                            $statistics[$answer->user_id][$genreId]['total']++;
                            if ($answer->correct) {
                                $statistics[$answer->user_id][$genreId]['correct']++;
                            }
                        }
                    }
                }

                $users = $users->map(function ($user) use ($statistics) {
                    $user->statistics = $statistics[$user->id];
                    return $user;
                });
            }
        }
        $currentNationalRanking = NationalRanking::orderBy('date', 'desc')->first();
        if ($currentNationalRanking) {
            $usersRankingPosition = array_reduce(
                $currentNationalRanking->getData()->ranking,
                function ($acc, $item) {
                    $acc[$item->individual_quiz_player_id] = $item->rank;
                    return $acc;
                },
                []
            );
            $users = $users->map(function ($user) use ($usersRankingPosition) {
                if (
                    isset($user->individual_quiz_player) &&
                    isset($usersRankingPosition[$user->individual_quiz_player->id])
                ) {
                    $user->national_rank =
                        $usersRankingPosition[$user->individual_quiz_player->id];
                }
                return $user;
            });
        }
        return $this->sendResponse(UserResource::collection($users), $partial ? 206 : 200);
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
                'roles.*' => [new RoleValue],
                'avatar' => 'base64image|base64max:200',
                'birthday' => 'nullable|date_format:Y-m-d|before:today',
                'region' => 'nullable|exists:regions,code',
                'reminders' => 'array',
            ]);
            if ($validator->fails()) {
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
            $user = $user->toArray();
            $user['avatar'] = $user['avatar_url'];
            unset($user['avatar_url']);
            $success['user'] = $user;

            return $this->sendResponse($success);
        }

        return $this->sendError('no_permissions', [], 403);
    }

    public function impersionate(Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            return $this->sendError('no_permissions', [], 403);
        }

        $input = $request::all();

        $validator = Validator::make($input, [
            'id' => 'required|exists:users,id',
        ]);
        if ($validator->fails()) {
            return $this->sendError('validation_error', $validator->errors(), 400);
        }

        $user = User::find($input['id']);
        $tokenResult = $user->createToken('Personal Access Token');
        $success['access_token'] = $tokenResult->accessToken;
        $success['token_type'] = 'Bearer';
        $tokenResult->token->expires_at = Carbon::now()->addMonth();
        $tokenResult->token->save();
        $success['expires_at'] = Carbon::parse(
            $tokenResult->token->expires_at
        )->toDateTimeString();
        $user = $user->toArray();
        $user['avatar'] = $user['avatar_url'];
        unset($user['avatar_url']);
        $success['user'] = $user;

        return $this->sendResponse($success);
    }
}
