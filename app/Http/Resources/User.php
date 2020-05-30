<?php

namespace App\Http\Resources;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Resources\Json\JsonResource;

class User extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        $user = [
            'id' => $this->id,
            'name' => $this->name,
            'surname' => $this->surname,
            'avatar' => $this->getAvatarUrlAttribute(),
        ];
        if(!$user['avatar']){
            unset($user['avatar']);
        }
        if ($request->get('id')) {
            if ($this->individual_quiz_player) {
                $user['individual_quiz_player_id'] = $this->individual_quiz_player['id'];
            }
        }
        if (Auth::user() && Auth::user()->isAdmin()) {
            $user['email'] = $this->email;
            $user['roles'] = $this->roles;
            $user['reminders'] = $this->reminders;
        };
        return $user;
    }
}
