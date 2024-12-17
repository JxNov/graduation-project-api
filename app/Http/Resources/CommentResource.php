<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'content' => $this->content,
            'createdAt' => $this->created_at,
            'id' => $this->id,
            'name' => optional($this->user)->name,
            'username' => optional($this->user)->username,
            'userImage' => optional($this->user)->image,
        ];
    }
}