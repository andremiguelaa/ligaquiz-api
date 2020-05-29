<?php

namespace App\Http\Resources;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Resources\Json\JsonResource;

class IndividualQuizResult extends JsonResource
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
        return [
            'result' => $this->result,
            'individual_quiz_type' => $this->individual_quiz->individual_quiz_type,
            'month' => substr($this->individual_quiz->date, 0, -3)
        ];
    }
}
