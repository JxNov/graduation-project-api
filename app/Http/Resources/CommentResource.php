<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => optional($this->user)->name,
            'articleTitle' => optional($this->article)->title,
            'content' => $this->content,
        ];
    }
}
