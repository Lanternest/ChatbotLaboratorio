<?php

namespace App\Services;

use App\Services\Contracts\LlmProviderInterface;
use App\Services\Providers\AnthropicProvider;
use App\Services\Providers\GeminiProvider;

class LlmProviderFactory
{
    public static function make(): LlmProviderInterface
    {
        $provider = env('LLM_PROVIDER', 'anthropic');
        
        return match (strtolower($provider)) {
            'gemini' => new GeminiProvider(),
            default => new AnthropicProvider(),
        };
    }
}
