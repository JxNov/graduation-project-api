<?php

use App\Models\Classes;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat-with-admin.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId || $user->isAdmin();
});

Broadcast::channel('attendance.{classId}', function ($user, $classId) {
    $class = Classes::find($classId);

    return $class && $class->students->contains('id', $user->id);
});

Broadcast::channel('article.{article_id}', function ($user, $article_id) {
    return true;
});
