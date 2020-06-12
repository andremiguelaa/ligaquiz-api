<?php

namespace App\Http\Resources;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Resources\Json\JsonResource;

class SpecialQuiz extends JsonResource
{
    public function toArray($request)
    {
        $specialquiz = [
            'date' => $this->date,
            'user_id' => $this->user_id,
            'subject' => $this->subject,
            'description' => $this->description,
        ];
        if (
            Auth::user()->hasPermission('specialquiz_create') ||
            Auth::user()->hasPermission('specialquiz_edit') ||
            Auth::user()->hasPermission('specialquiz_play')
        ) {
            $specialquiz['questions'] = $this->getQuestions();
        };
        return $specialquiz;
    }
}
