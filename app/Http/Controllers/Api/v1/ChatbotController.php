<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\ChatbotService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ChatbotController extends Controller
{
    protected $chatbotService;

    public function __construct(ChatbotService $chatbotService)
    {
        $this->chatbotService = $chatbotService;
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
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
