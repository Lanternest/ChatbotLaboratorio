<?php

namespace App\Services\Providers;

use App\Services\Contracts\LlmProviderInterface;
use Illuminate\Support\Facades\Http;

class AnthropicProvider implements LlmProviderInterface
{
    private const URL = 'https://api.anthropic.com/v1/messages';
    private const MODEL = 'claude-3-5-sonnet-20241022';

    public function generarRespuestaChat(string $systemPrompt, array $historial, string $mensaje): string
    {
        $apiKey = env('ANTHROPIC_API_KEY', config('services.anthropic.api_key'));
        if (empty($apiKey) || str_starts_with($apiKey, 'sk-ant-...')) {
            throw new \Exception("La API Key de Anthropic (Claude) no está configurada o es inválida en el archivo .env (ANTHROPIC_API_KEY)");
        }

        $mensajesApi = array_map(
            fn ($m) => ['role' => $m['role'], 'content' => $m['content']],
            $historial
        );
        $mensajesApi[] = ['role' => 'user', 'content' => $mensaje];

        $response = Http::withHeaders([
            'x-api-key'         => $apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->post(self::URL, [
            'model'      => self::MODEL,
            'max_tokens' => 1000,
            'system'     => $systemPrompt,
            'messages'   => $mensajesApi,
        ]);

        return trim($response->json('content.0.text', ''));
    }

    public function identificarAnalisis(string $prompt, ?string $imagenBase64 = null, ?string $mimeType = null): string
    {
        $apiKey = env('ANTHROPIC_API_KEY', config('services.anthropic.api_key'));
        if (empty($apiKey) || str_starts_with($apiKey, 'sk-ant-...')) {
            throw new \Exception("La API Key de Anthropic (Claude) no está configurada o es inválida en el archivo .env (ANTHROPIC_API_KEY)");
        }

        $content = [];
        
        if ($imagenBase64 && $mimeType) {
            $content[] = [
                'type' => 'image',
                'source' => [
                    'type' => 'base64',
                    'media_type' => $mimeType,
                    'data' => $imagenBase64,
                ]
            ];
        }
        
        $content[] = [
            'type' => 'text',
            'text' => $prompt
        ];

        $response = Http::withHeaders([
            'x-api-key'         => $apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->post(self::URL, [
            'model'      => self::MODEL,
            'max_tokens' => 150,
            'messages'   => [
                [
                    'role' => 'user',
                    'content' => $content
                ]
            ],
        ]);

        return trim($response->json('content.0.text', '[]'));
    }
}
