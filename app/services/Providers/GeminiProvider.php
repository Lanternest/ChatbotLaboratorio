<?php

namespace App\Services\Providers;

use App\Services\Contracts\LlmProviderInterface;
use Illuminate\Support\Facades\Http;

class GeminiProvider implements LlmProviderInterface
{
    private const MODEL = 'gemini-flash-lite-latest';

    private function getUrl(): string
    {
        $apiKey = env('GEMINI_API_KEY', config('services.gemini.api_key'));
        if (empty($apiKey)) {
            throw new \Exception("La API Key de Gemini no está configurada en el archivo .env (GEMINI_API_KEY)");
        }
        return "https://generativelanguage.googleapis.com/v1beta/models/" . self::MODEL . ":generateContent?key={$apiKey}";
    }

    public function generarRespuestaChat(string $systemPrompt, array $historial, string $mensaje, ?string $imagenBase64 = null, ?string $mimeType = null): string
    {
        $contents = [];
        
        foreach ($historial as $msg) {
            $role = $msg['role'] === 'assistant' ? 'model' : 'user';
            $contents[] = [
                'role' => $role,
                'parts' => [['text' => $msg['content']]]
            ];
        }
        
        $userParts = [];
        if ($imagenBase64 && $mimeType) {
            $userParts[] = [
                'inline_data' => [
                    'mime_type' => $mimeType,
                    'data' => $imagenBase64
                ]
            ];
        }
        $userParts[] = ['text' => $mensaje ?: "Aquí tienes una imagen adjunta."];
        
        $contents[] = [
            'role' => 'user',
            'parts' => $userParts
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($this->getUrl(), [
            'system_instruction' => [
                'parts' => [['text' => $systemPrompt]]
            ],
            'contents' => $contents,
            'generationConfig' => [
                'maxOutputTokens' => 1000,
            ]
        ]);

        if ($response->failed()) {
            throw new \Exception("Error de Gemini API: " . $response->body());
        }

        return trim($response->json('candidates.0.content.parts.0.text', ''));
    }

    public function identificarAnalisis(string $prompt, ?string $imagenBase64 = null, ?string $mimeType = null): string
    {
        $parts = [];
        
        if ($imagenBase64 && $mimeType) {
            $parts[] = [
                'inline_data' => [
                    'mime_type' => $mimeType,
                    'data' => $imagenBase64
                ]
            ];
        }
        
        $parts[] = [
            'text' => $prompt
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($this->getUrl(), [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => $parts
                ]
            ],
            'generationConfig' => [
                'maxOutputTokens' => 150,
                'responseMimeType' => 'application/json',
            ]
        ]);

        if ($response->failed()) {
            throw new \Exception("Error de Gemini API (Identificar): " . $response->body());
        }

        return trim($response->json('candidates.0.content.parts.0.text', '[]'));
    }
}
