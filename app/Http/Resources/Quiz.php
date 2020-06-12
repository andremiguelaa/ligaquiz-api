<?php

namespace App\Http\Resources;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Resources\Json\JsonResource;

class Quiz extends JsonResource
{
    public function toArray($request)
    {
        $quiz = [
            'date' => $this->date
        ];
        if (
            Auth::user()->hasPermission('quiz_create') ||
            Auth::user()->hasPermission('quiz_edit') ||
            Auth::user()->hasPermission('quiz_play')
        ) {
            $quiz['questions'] = $this->getQuestions();
        };
        return $quiz;
    }
}
