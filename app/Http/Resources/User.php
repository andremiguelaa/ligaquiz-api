<?php

namespace App\Http\Resources;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Resources\Json\JsonResource;

class User extends JsonResource
{
    public function toArray($request)
    {
        $user = [
            'id' => $this->id,
            'name' => $this->name,
            'surname' => $this->surname,
            'avatar' => $this->getAvatarUrlAttribute(),
            'valid_roles' => $this->valid_roles,
        ];
        if (!$user['avatar']) {
            unset($user['avatar']);
        }
        if (isset($this->statistics)) {
            $user['statistics'] = (object) $this->statistics;
        }
        if (isset($this->national_rank)) {
            $user['national_rank'] = $this->national_rank;
        }
        if ($request->get('id')) {
            if ($this->individual_quiz_player) {
                $user['individual_quiz_player_id'] = $this->individual_quiz_player['id'];
            }
        }
        if (Auth::user() && (Auth::user()->isAdmin() || Auth::user()->hasBirthday())) {
            $user['birthday'] = $this->birthday;
        }
        if (Auth::user() && (Auth::user()->isAdmin() || Auth::user()->hasRegion())) {
            $user['region'] = $this->region;
        }
        if (Auth::user() && Auth::user()->isAdmin()) {
            $user['email'] = $this->email;
            $user['roles'] = $this->roles;
            $user['reminders'] = $this->reminders;
        } else {
            unset($user['valid_roles']);
        }
        return $user;
    }
}
