<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\ChatbotService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class ChatbotController extends Controller
{
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
            $data = [];

            foreach ($chats as $chat) {
                $date = $chat->created_at->format('d/m/Y');
                foreach ($chat->content as $item) {
                    $data[] = [
                        'date' => $date,
                        'question' => $item['question'],
                        'answer' => $item['answer'],
                    ];
                }
            }

            $groupedByDate = collect($data)->groupBy('date');

            $result = $groupedByDate->map(function ($items) {
                return $items->values()->all();
            });

            return response()->json($result);
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
