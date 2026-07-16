<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ChatbotService
{
    private const ANTHROPIC_URL = 'https://api.anthropic.com/v1/messages';
    private const MODEL         = 'claude-sonnet-4-6';

    private string $promptBase = <<<'PROMPT'
Eres el asistente virtual del Servicio de Análisis Clínicos del Hospital Universitario UNCuyo.
Tu función es orientar a los pacientes sobre las condiciones previas necesarias para realizarse un análisis clínico.

REGLAS QUE DEBES CUMPLIR SIEMPRE:
1. Responde ÚNICAMENTE con la información del instructivo oficial que se te provee. No inventes, no supongas, no improvises condiciones médicas.
2. Si no tenés información sobre algo, indicá que el paciente se comunique con el laboratorio.
3. Si el paciente no sabe el nombre del análisis, hacé preguntas breves (máximo una por turno) para ayudar a identificarlo. Nunca sugieras un análisis sin estar seguro.
4. El servicio funciona por DEMANDA ESPONTÁNEA: no hay turnos. El paciente simplemente concurre al laboratorio en el horario indicado.
5. Mantené un tono claro, amable y simple. El paciente puede no tener conocimientos médicos.
6. Cuando el paciente haya resuelto todas sus dudas, preguntale si desea recibir un resumen de las condiciones por correo electrónico.
7. Si el paciente pide el resumen, generalo en formato claro y estructurado (detallado abajo).

FORMATO DEL RESUMEN (cuando el paciente lo solicite):
---
RESUMEN DE PREPARACIÓN PARA TU ANÁLISIS
Hospital Universitario UNCuyo - Servicio de Análisis Clínicos

Análisis: [nombre del análisis]
Horario de extracción: [horario]
[Días disponibles si aplica]
Tipo de muestra: [tipo]
Ayuno: [sí/no y detalles]
[Duración del estudio si aplica]

Condiciones previas:
[listado de indicaciones]

[Insumos a conseguir antes de ir, si aplica]

Ante cualquier duda: {email} {telefono}
---

Cuando incluyas el resumen en tu respuesta, marcalo con las etiquetas <resumen> y </resumen>.
PROMPT;

    private string $promptSinAnalisis = <<<'PROMPT'
No has podido identificar el análisis del paciente.
Indicale amablemente que no encontraste información sobre ese análisis en tu base de datos y derivalo al laboratorio:
- Email: {email}
- Teléfono: {telefono}
No intentes adivinar ni inventar información.
PROMPT;

    public function __construct(private KnowledgeService $knowledge) {}

    /**
     * Genera la respuesta del chatbot.
     * Devuelve array con: respuesta, listo_para_enviar, resumen.
     */
    public function generarRespuesta(string $mensaje, array $historial, ?string $analisisId): array
    {
        $contacto = $this->knowledge->obtenerContacto();
        $email    = $contacto['email'] ?? 'informeslaboratorio.hu@gmail.com';
        $telefono = $contacto['telefono'] ?? 'consultar con el laboratorio';

        if ($analisisId) {
            $contexto = $this->knowledge->obtenerContextoAnalisis($analisisId);
            $system = str_replace(
                ['{email}', '{telefono}'],
                [$email, $telefono],
                $this->promptBase
            );
            $system .= "\n\nINFORMACIÓN OFICIAL DEL ANÁLISIS SOLICITADO:\n{$contexto}";
        } else {
            $system = str_replace(
                ['{email}', '{telefono}'],
                [$email, $telefono],
                $this->promptSinAnalisis
            );
        }

        $mensajesApi = array_map(
            fn ($m) => ['role' => $m['role'], 'content' => $m['content']],
            $historial
        );
        $mensajesApi[] = ['role' => 'user', 'content' => $mensaje];

        $response = Http::withHeaders([
            'x-api-key'         => config('services.anthropic.api_key'),
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->post(self::ANTHROPIC_URL, [
            'model'      => self::MODEL,
            'max_tokens' => 1000,
            'system'     => $system,
            'messages'   => $mensajesApi,
        ]);

        $texto = trim($response->json('content.0.text', ''));

        // Detectar si la respuesta incluye un resumen listo para enviar
        $resumen          = null;
        $listoParaEnviar  = false;

        if (str_contains($texto, '<resumen>') && str_contains($texto, '</resumen>')) {
            $inicio  = strpos($texto, '<resumen>') + strlen('<resumen>');
            $fin     = strpos($texto, '</resumen>');
            $resumen = trim(substr($texto, $inicio, $fin - $inicio));
            $listoParaEnviar = true;

            // Limpiar las etiquetas del texto que ve el paciente
            $texto = str_replace("<resumen>{$resumen}</resumen>", $resumen, $texto);
        }

        return [
            'respuesta'         => $texto,
            'listo_para_enviar' => $listoParaEnviar,
            'resumen'           => $resumen,
        ];
    }
}