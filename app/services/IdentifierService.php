<?php

namespace App\Services;

class IdentifierService
{
    private string $promptTemplate = <<<'PROMPT'
Eres un asistente experto del laboratorio del Hospital Universitario UNCuyo, especializado en leer y descifrar órdenes médicas.

Tu única tarea es determinar qué análisis de laboratorio se requieren, basándote EXCLUSIVAMENTE en el catálogo y en el mensaje o la imagen de la orden médica del paciente.

CATÁLOGO DE ANÁLISIS DISPONIBLES:
{catalogo}

INSTRUCCIONES CRÍTICAS:
- Analiza el texto o la imagen proporcionada. Presta atención a la escritura cursiva o abreviaturas médicas comunes.
- Identifica TODOS los análisis que el médico está solicitando.
- Responde ÚNICAMENTE con un arreglo (array) en formato JSON puro que contenga los IDs exactos del catálogo. Ejemplo: ["cultivo_de_orina", "curva_de_glucemia_o_de_insulina"].
- Si identificas solo uno, devuelve un arreglo con un elemento: ["hemograma_completo"].
- Si NO puedes identificar ningún análisis, responde ÚNICAMENTE con un arreglo vacío: []
- No incluyas explicaciones, saludos, ni texto adicional fuera del arreglo JSON.
- Nunca inventes IDs que no estén en el catálogo.

Historial de conversación:
{historial}

Último mensaje del paciente: {mensaje}

Responde solo con el JSON (Array):
PROMPT;

    public function __construct(private KnowledgeService $knowledge) {}

    /**
     * Intenta identificar los análisis clínicos mencionados por el paciente o en la foto.
     * Devuelve un arreglo con los IDs de los análisis, o un arreglo vacío si no pudo.
     */
    public function identificar(string $mensaje, array $historial, ?string $imagenBase64 = null, ?string $mimeType = null): array
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

        $llm = LlmProviderFactory::make();
        $resultado = $llm->identificarAnalisis($prompt, $imagenBase64, $mimeType);

        // Intentar parsear el JSON de la respuesta
        // Limpiar backticks o texto extra que a veces las IA añaden (ej. ```json ... ```)
        $resultadoLimpio = trim(str_replace(['```json', '```'], '', $resultado));

        $ids = json_decode($resultadoLimpio, associative: true);

        return is_array($ids) ? $ids : [];
    }
}