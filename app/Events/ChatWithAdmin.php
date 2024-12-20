<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatWithAdmin implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public $messageId, public $message, public $isRead, public User $user)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat-with-admin.' . $this->user->username),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat';
    }

    public function broadcastWith()
    {
        return [
            'messageID' => $this->messageId,
            'message' => $this->message,
            'isRead' => $this->isRead,
            'name' => $this->user->name,
            'username' => $this->user->username,
        ];
    }
}

