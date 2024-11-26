<?php

namespace App\Services;

use Exception;
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
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($this->apiUrl . '?key=' . $this->apiKey, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $question]
                            ]
                        ]
                    ]
                ]);

        if ($response->successful()) {
            $data = $response->json();
            $answer = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Không có câu trả lời';
            return $answer;
        }

        throw new Exception("Lỗi khi hỏi: " . $response->body());
    }
}
