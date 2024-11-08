<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'conversationId' => $this->conversation_id,
            'userId' => $this->user_id,
            'message' => $this->message,
            'isRead' => $this->is_read,
            'conversationName' => optional($this->conversation)->title,
            'username' => optional($this->user)->name,
        ];
    }
}
