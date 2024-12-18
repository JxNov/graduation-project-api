<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatBotSession extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'chat_bot_sessions';

    protected $fillable = [
        'user_id',
        'title',
        'content',
    ];

    protected $casts = [
        'content' => 'array',
    ];
}
