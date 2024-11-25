<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    use ApiResponseTrait;

    public function index()
    {
        try {
            $user = Auth::user();

            $notifications = $user->notifications;

            if ($notifications->isEmpty()) {
                return $this->errorResponse('Không có thông báo', Response::HTTP_NOT_FOUND);
            }

            $unreadCount = $user->unreadNotifications->count();

            $data = $notifications->map(function ($notification) use ($unreadCount) {
                $notificationData = $notification->data;
                return [
                    'id' => $notification->id,
                    'notifyTitle' => $notificationData['notifyTitle'] ?? null,
                    'assignmentTitle' => $notificationData['assignmentTitle'] ?? null,
                    'assignmentSlug' => $notificationData['assignmentSlug'] ?? null,
                    'isRead' => $notification->read_at ? true : false, // trạng thái đã đọc hay chưa
                    'unreadCount' => $unreadCount, // thông báo chưa đọc
                ];
            });

            return $this->successResponse(
                $data,
                'Lấy danh sách thông báo thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function markAsRead($notificationId)
    {
        try {
            $user = Auth::user();

            $notification = $user->notifications()->findOrFail($notificationId);
            $notification->markAsRead();

            return $this->successResponse(
                null,
                'Đánh dấu thông báo đã đọc thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);

        }
    }

    public function markAllAsRead()
    {
        try {
            $user = Auth::user();

            $user->unreadNotifications->markAsRead();

            return $this->successResponse(
                null,
                'Đánh dấu tất cả thông báo đã đọc thành công',
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);

        }
    }
}
