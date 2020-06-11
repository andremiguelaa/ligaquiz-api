<?php

namespace App\Http\Resources;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Resources\Json\JsonResource;

class Quiz extends JsonResource
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
        $quiz = [
            'date' => $this->date
        ];
        if (Auth::user()->hasPermission('quiz_create')) {
            $quiz['questions'] = $this->getQuestions();
        };
        return $quiz;
    }
}
