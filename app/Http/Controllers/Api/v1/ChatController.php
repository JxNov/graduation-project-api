<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\MessageCollection;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Role;
use App\Models\User;
use App\Services\ChatService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class ChatController extends Controller
{
    use ApiResponseTrait;

    protected $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    public function getConversationAdmin()
    {
        $roleAdmin = Role::select('id', 'slug')->where('slug', 'admin')->first();

        if ($roleAdmin === null) {
            return $this->errorResponse('Không tìm thấy vai trò quản trị', Response::HTTP_NOT_FOUND);
        }

        $admin = User::whereHas('roles', function ($query) use ($roleAdmin) {
            $query->where('role_id', $roleAdmin->id);
        })->first();

        if ($admin === null) {
            return $this->errorResponse('Không tìm thấy người quản trị', Response::HTTP_NOT_FOUND);
        }

        $conversations = Conversation::whereHas('users', function ($query) use ($admin) {
            $query->where('user_id', $admin->id);
        })
            ->select('id', 'title')
            ->get();

        return $this->successResponse(
            $conversations,
            'Lấy tất cả tin nhắn với quản trị thành công',
            Response::HTTP_OK
        );
    }

    public function getMessageUserToAdmin($conversationID)
    {
        $conversation = Conversation::where('id', $conversationID)->first();

        if ($conversation === null) {
            return $this->errorResponse('Không tìm thấy cuộc trò chuyện', Response::HTTP_NOT_FOUND);
        }

        $messages = $conversation->messages()->get();

        return $this->successResponse(
            new MessageCollection($messages),
            'Lấy tất cả tin nhắn với người dùng thành công',
            Response::HTTP_OK
        );
    }

    // public function getMessageUser($conversationID)
    // {
    //     $conversation = Conversation::where('id', $conversationID)->first();

    //     if ($conversation === null) {
    //         return $this->errorResponse('Không tìm thấy cuộc trò chuyện', Response::HTTP_NOT_FOUND);
    //     }

    //     $student = JWTAuth::parseToken()->authenticate();

    //     if (!$student) {
    //         throw new Exception('Bạn chưa đăng nhập');
    //     }
    // }

    public function sendMessageToAdmin(Request $request)
    {
        try {
            $message = $request->message;
            $message = $this->chatService->sendMessageToAdmin($request->message);

            return $this->successResponse(
                $message->message,
                'Gửi tin nhắn cho quản trị thành công',
                Response::HTTP_CREATED
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function sendMessageToStudent(Request $request, $studentId)
    {
        try {
            $message = $request->message;
            $studentId = $request->studentId;
            
            $message = $this->chatService->sendMessageToStudent($request->message, $studentId);

            return $this->successResponse(
                $message->message,
                'Gửi tin nhắn cho quản trị thành công',
                Response::HTTP_CREATED
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }


}
