<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class IdentifierService
{
    private const ANTHROPIC_URL = 'https://api.anthropic.com/v1/messages';
    private const MODEL          = 'claude-sonnet-4-6';

    private string $promptTemplate = <<<'PROMPT'
Eres un asistente de identificación de análisis clínicos del laboratorio del Hospital Universitario UNCuyo.

Tu única tarea es determinar a qué análisis se refiere el paciente, basándote EXCLUSIVAMENTE en el catálogo que se te proporciona a continuación.

CATÁLOGO DE ANÁLISIS DISPONIBLES:
{catalogo}

INSTRUCCIONES:
- Analiza el mensaje del paciente y el historial de la conversación.
- Si podés identificar con certeza el análisis, responde ÚNICAMENTE con el ID exacto del análisis (por ejemplo: cultivo_de_orina).
- Si NO podés identificar el análisis con certeza, responde ÚNICAMENTE con la palabra: null
- No des explicaciones. No saludes. No hagas preguntas. Solo devuelve el ID o null.
- Nunca inventes IDs que no estén en el catálogo.

Historial de conversación:
{historial}

Último mensaje del paciente: {mensaje}

Responde solo con el ID del análisis o null:
PROMPT;

    public function __construct(private KnowledgeService $knowledge) {}

    /**
     * Intenta identificar el análisis clínico mencionado por el paciente.
     * Devuelve el id del análisis o null si no pudo identificarlo.
     */
    public function identificar(string $mensaje, array $historial): ?string
    {
        $catalogo = $this->knowledge->catalogoParaIdentificacion();

        $historialTexto = empty($historial)
            ? '(sin historial previo)'
            : implode("\n", array_map(
                fn($m) => strtoupper($m['role']) . ': ' . $m['content'],
                $historial
            ));

        $prompt = str_replace(
            ['{catalogo}', '{historial}', '{mensaje}'],
            [$catalogo, $historialTexto, $mensaje],
            $this->promptTemplate
        );

        $response = Http::withHeaders([
            'x-api-key'         => config('services.anthropic.api_key'),
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->post(self::ANTHROPIC_URL, [
            'model'      => self::MODEL,
            'max_tokens' => 50,
            'messages'   => [['role' => 'user', 'content' => $prompt]],
        ]);

        $resultado = strtolower(trim(
            $response->json('content.0.text', 'null')
        ));

        return ($resultado === 'null' || empty($resultado)) ? null : $resultado;
    }
}