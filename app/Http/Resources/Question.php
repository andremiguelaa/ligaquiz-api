<?php

namespace App\Http\Resources;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Resources\Json\JsonResource;

class Question extends JsonResource
{
    public function toArray($request)
    {
        
        $question = [
            'id' => $this->id,
            'content' => $this->content,
            'answer' => $this->answer,
            'media' => $this->media,
            'genre' => $this->genre
        ];
        return array_filter($question, function($item) {
            return $item;
        });
    }
}
