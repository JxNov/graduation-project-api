<?php

namespace App\Services;

use App\Models\ChatBotSession;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ChatbotService
{
    protected $apiUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->apiUrl = env('GEMINI_API_URL');
        $this->apiKey = env('GEMINI_API_KEY');
    }

    public function ask($question)
    {
        try {
            return $this->askQuestion($question);
        } catch (Exception $e) {
            throw $e;
        }
    }

    protected function askQuestion($question)
    {
        return DB::transaction(function () use ($question) {
            $promptTitle = "Tạo một tiêu đề ngắn gọn chỉ có 20 ký tự cho câu hỏi: \"$question\"";

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '?key=' . $this->apiKey, [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $question],
                                ]
                            ]
                        ]
                    ]);

            $responseTitle = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '?key=' . $this->apiKey, [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $promptTitle],
                                ]
                            ]
                        ]
                    ]);

            if ($response->successful() && $responseTitle->successful()) {
                $dayNow = Carbon::now()->toDateString();

                $data = $response->json();
                $dataTitle = $responseTitle->json();

                $title = $dataTitle['candidates'][0]['content']['parts'][0]['text'] ?? now();
                $answer = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Không có câu trả lời';

                $exitChatBotSessionUser = ChatBotSession::where('user_id', Auth::id())
                    ->whereDate('created_at', $dayNow)
                    ->first();

                if ($exitChatBotSessionUser) {
                    $content = $exitChatBotSessionUser->content;
                    $content[] = [
                        'question' => $question,
                        'answer' => $answer,
                    ];

                    $exitChatBotSessionUser->update([
                        'content' => $content,
                    ]);
                } else {
                    ChatBotSession::create([
                        'user_id' => Auth::id(),
                        'title' => $title,
                        'content' => [
                            [
                                'question' => $question,
                                'answer' => $answer,
                            ],
                        ],
                    ]);
                }

                return $answer;
            }

            throw new Exception("Lỗi khi hỏi: " . $response->body());
        });
    }

}
