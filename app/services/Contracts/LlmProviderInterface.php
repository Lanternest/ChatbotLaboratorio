<?php

namespace App\Services\Contracts;

interface LlmProviderInterface
{
    /**
     * Genera la respuesta del chatbot conversacional.
     *
     * @param string $systemPrompt
     * @param array $historial
     * @param string $mensaje
     * @return string
     */
    public function generarRespuestaChat(string $systemPrompt, array $historial, string $mensaje, ?string $imagenBase64 = null, ?string $mimeType = null): string;

    /**
     * Identifica los análisis clínicos a partir de un mensaje y/o imagen.
     *
     * @param string $prompt
     * @param string|null $imagenBase64
     * @param string|null $mimeType
     * @return string JSON o texto con los IDs
     */
    public function identificarAnalisis(string $prompt, ?string $imagenBase64 = null, ?string $mimeType = null): string;
}
