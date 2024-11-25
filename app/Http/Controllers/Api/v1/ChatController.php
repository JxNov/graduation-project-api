<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\MessageRequest;
use App\Models\Conversation;
use App\Services\ChatService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

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
            $user = Auth::user();

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

            $this->markAsRead($conversation);

            $messages = $conversation->messages()->get();

            $data = $messages->groupBy('conversation_id')->map(function ($messageGroup) {
                $messages = $messageGroup->map(function ($message) {
                    return [
                        'messageID' => $message->id,
                        'message' => $message->message,
                        'isRead' => $message->is_read,
                        'name' => $message->user->name,
                        'username' => $message->user->username,
                    ];
                });

                return [
                    'conversationId' => $messageGroup->first()->conversation_id,
                    'messages' => $messages,
                ];
            });

            return $this->successResponse(
                $data->values(),
                'Lấy tất cả tin nhắn với người dùng thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function getConversationStudent()
    {
        try {
            $student = Auth::user();

            $conversations = Conversation::whereHas('users', function ($query) use ($student) {
                $query->where('user_id', $student->id);
            })
                ->select('id', 'title')
                ->get();

            if ($conversations->isEmpty()) {
                return $this->errorResponse('Chưa có cuộc trò chuyện nào', Response::HTTP_NOT_FOUND);
            }

            return $this->successResponse(
                $conversations,
                'Lấy tất cả cuộc trò chuyện của người dùng thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function getMessageAdminToStudent($conversationID)
    {
        try {
            $conversation = Conversation::where('id', $conversationID)->first();

            if ($conversation === null) {
                return $this->errorResponse('Không tìm thấy cuộc trò chuyện', Response::HTTP_NOT_FOUND);
            }

            $this->markAsRead($conversation);

            $messages = $conversation->messages()->get();

            $data = $messages->groupBy('conversation_id')->map(function ($messageGroup) {
                $messages = $messageGroup->map(function ($message) {
                    return [
                        'messageID' => $message->id,
                        'message' => $message->message,
                        'isRead' => $message->is_read,
                        'name' => $message->user->name,
                        'username' => $message->user->username,
                    ];
                });

                return [
                    'conversationId' => $messageGroup->first()->conversation_id,
                    'messages' => $messages,
                ];
            });

            return $this->successResponse(
                $data->values(),
                'Lấy tất cả tin nhắn với người dùng thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function sendMessageToAdmin(MessageRequest $request)
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

    public function sendMessageToStudent(MessageRequest $request, $username)
    {
        try {
            $message = $request->message;
            $username = $request->username;

            $message = $this->chatService->sendMessageToStudent($request->message, $username);

            return $this->successResponse(
                $message->message,
                'Gửi tin nhắn cho học sinh thành công',
                Response::HTTP_CREATED
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function updateMessage(MessageRequest $request, $messageID)
    {
        try {
            $message = $request->message;
            $message = $this->chatService->updateMessage($message, $messageID);

            $message = [
                'messageID' => $message->id,
                'message' => $message->message,
                'isRead' => $message->is_read,
                'name' => $message->user->name,
                'username' => $message->user->username,
            ];

            return $this->successResponse(
                $message,
                'Đã sửa tin nhắn thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function markAsRead($conversation)
    {
        foreach ($conversation->messages as $message) {
            $message->is_read = true;
            $message->save();
        }
    }
}
