<?php

namespace App\Http\Resources;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Resources\Json\JsonResource;

class SpecialQuiz extends JsonResource
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
        $specialquiz = [
            'date' => $this->date,
            'user_id' => $this->user_id,
            'subject' => $this->subject,
            'description' => $this->description,
        ];
        if (Auth::user()->hasPermission('specialquiz_create')) {
            $specialquiz['questions'] = $this->getQuestions();
        };
        return $specialquiz;
    }
}
