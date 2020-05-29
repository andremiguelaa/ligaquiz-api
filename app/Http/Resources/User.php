<?php

namespace App\Http\Resources;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\IndividualQuizResult as IndividualQuizResultResource;

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
        if ($request->get('id')) {
            if ($this->individual_quiz_player) {
                $user['individual_quiz_player_id'] = $this->individual_quiz_player['id'];
            }
            if ($this->individual_quiz_results) {
                $user['individual_quiz_results'] = IndividualQuizResultResource::collection($this->individual_quiz_results);
            }
        }
        if (Auth::user()->isAdmin()) {
            $user['email'] = $this->email;
            $user['roles'] = $this->roles;
            $user['reminders'] = $this->reminders;
        };
        return $user;
    }
}
