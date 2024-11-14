<?php

use App\Models\Classes;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

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