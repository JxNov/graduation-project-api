<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\MessageCollection;
use App\Models\Conversation;
use App\Services\ChatService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

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
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return $this->errorResponse('Không có quyền truy cập', Response::HTTP_FORBIDDEN);
            }

            if ($user->isAdmin()) {
                $conversations = Conversation::whereHas('users', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                    ->select('id', 'title')
                    ->get();

                if ($conversations->isEmpty()) {
                    return $this->errorResponse('Chưa có cuộc trò chuyện nào', Response::HTTP_NOT_FOUND);
                }

                return $this->successResponse(
                    $conversations,
                    'Lấy tất cả cuộc trò chuyện của quản trị thành công',
                    Response::HTTP_OK
                );
            } else {
                return $this->errorResponse('Bạn không có quyền truy cập', Response::HTTP_FORBIDDEN);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function getMessageStudentToAdmin($conversationID)
    {
        try {
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
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    // public function getConversationStudent()
    // {
    //     try {
    //         $student = JWTAuth::parseToken()->authenticate();

    //         $conversations = Conversation::whereHas('users', function ($query) use ($student) {
    //             $query->where('user_id', $student->id);
    //         })
    //             ->select('id', 'title')
    //             ->get();

    //         if ($conversations->isEmpty()) {
    //             return $this->errorResponse('Chưa có cuộc trò chuyện nào', Response::HTTP_NOT_FOUND);
    //         }

    //         return $this->successResponse(
    //             $conversations,
    //             'Lấy tất cả cuộc trò chuyện của người dùng thành công',
    //             Response::HTTP_OK
    //         );
    //     } catch (Exception $e) {
    //         return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
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
