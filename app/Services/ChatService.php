<?php

namespace App\Services;

use App\Events\ChatWithAdmin;
use App\Events\MessageToAdmin;
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

    public function sendMessageToStudent(string $message, int $studentId)
    {
        return DB::transaction(function () use ($message, $studentId) {
            $admin = JWTAuth::parseToken()->authenticate();

            $student = User::find($studentId);

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