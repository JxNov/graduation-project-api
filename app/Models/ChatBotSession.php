<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatBotSession extends Model
{
    use HasFactory;

    protected $table = 'chat_bot_sessions';

    protected $fillable = [
        'user_id',
        'title',
        'content',
    ];
}
