<?php

namespace App\Services;

use App\Events\ChatWithAdmin;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Role;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class ChatService
{
    public function sendMessageToAdmin(string $message)
    {
        return DB::transaction(function () use ($message) {
            $admin = $this->getAdmin();

            $student = JWTAuth::parseToken()->authenticate();

            if (!$student) {
                throw new Exception('Bạn chưa đăng nhập');
            }

            $conversation = Conversation::firstOrCreate(
                ['title' => $student->name]
            );

            $this->createConversationUser($conversation, $admin, $student);

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => $student->id,
                'message' => $message,
                'is_read' => false,
            ]);

            broadcast(new ChatWithAdmin($message->message, $student));

            return $message;
        });
    }

    public function sendMessageToStudent(string $message, string $username)
    {
        return DB::transaction(function () use ($message, $username) {
            $admin = JWTAuth::parseToken()->authenticate();

            $student = User::where('username', $username)->first();

            if (!$student) {
                throw new Exception('Không tìm thấy học sinh');
            }

            $conversation = Conversation::firstOrCreate(
                ['title' => $student->name]
            );

            $this->createConversationUser($conversation, $admin, $student);

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => $admin->id,
                'message' => $message,
                'is_read' => false,
            ]);

            broadcast(new ChatWithAdmin($message->message, $student));

            return $message;
        });
    }

    public function updateMessage($messageContent, $messageID)
    {
        return DB::transaction(function () use ($messageContent, $messageID) {
            $user = JWTAuth::parseToken()->authenticate();

            $message = Message::where('id', $messageID)
                ->where('user_id', $user->id)
                ->first();

            if (!$message) {
                throw new Exception('Không tìm thấy tin nhắn');
            }

            $message->message = $messageContent;

            $message->save();

            return $message;
        });
    }

    private function getAdmin()
    {
        $roleAdmin = Role::select('id', 'slug')->where('slug', 'admin')->first();

        if ($roleAdmin === null) {
            throw new Exception('Không tìm thấy vai trò quản trị');
        }

        $admin = User::whereHas('roles', function ($query) use ($roleAdmin) {
            $query->where('role_id', $roleAdmin->id);
        })->first();

        if (!$admin) {
            throw new Exception('Không tìm thấy người quản trị');
        }

        return $admin;
    }

    public function createConversationUser($conversation, $admin, $student)
    {
        DB::table('conversation_users')->insert([
            'conversation_id' => $conversation->id,
            'user_id' => $student->id,
        ]);

        DB::table('conversation_users')->insert([
            'conversation_id' => $conversation->id,
            'user_id' => $admin->id,
        ]);
    }
}