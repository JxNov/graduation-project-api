<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\ChatbotService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class ChatbotController extends Controller
{
    use ApiResponseTrait;
    protected $chatbotService;

    public function __construct(ChatbotService $chatbotService)
    {
        $this->chatbotService = $chatbotService;
    }

    public function index()
    {
        try {
            $user = Auth::user();

            $chats = $user->chatBotSessions;

            $data = $chats->map(function ($chat) {
                return [
                    'id' => $chat->id,
                    'title' => $chat->title,
                    'content' => collect($chat->content)->map(function ($item) {
                        return [
                            'question' => $item['question'],
                            'answer' => $item['answer'],
                        ];
                    })->toArray(),
                ];
            })->toArray();

            return $this->successResponse($data);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function ask(Request $request)
    {
        try {
            $request->validate([
                'question' => 'required',
            ]);

            $answer = $this->chatbotService->ask($request->question);

            return response()->json($answer);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
