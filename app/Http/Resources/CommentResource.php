<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'articleTitle' => optional($this->article)->title,
            'name' => optional($this->user)->name,
            'userImage' => optional($this->user)->image,
            'createdAt' => $this->created_at,
        ];
    }
}