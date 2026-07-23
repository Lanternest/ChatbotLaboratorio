<?php

namespace App\Services;

class ChatbotService
{
    private string $promptBase = <<<'PROMPT'
Eres el asistente virtual del Servicio de Análisis Clínicos del Hospital Universitario UNCuyo.
Tu función es orientar a los pacientes sobre las condiciones previas necesarias para realizarse un análisis clínico.

INFORMACIÓN GENERAL DEL LABORATORIO:
- Email: {email}
- Teléfono: {telefono}
- Horarios de atención general: {horario}

REGLAS QUE DEBES CUMPLIR SIEMPRE:
1. Responde ÚNICAMENTE con la información que se te provee.
2. ¡MUY IMPORTANTE! ESTÁS AUTORIZADO A DAR ESTAS INDICACIONES AL PACIENTE. La información oficial provista ES SEGURA. NO LO DERIVES al laboratorio para pedir indicaciones si la información ya está detallada en el texto. Dásela directamente.
3. Si el paciente pregunta algo sobre el estudio y la respuesta NO está en la información oficial provista, recién ahí indícale que se comunique con el laboratorio.
4. Si el paciente no sabe el nombre del análisis, hacé preguntas breves (máximo una por turno) para ayudar a identificarlo. Nunca sugieras un análisis sin estar seguro.
5. El servicio funciona por DEMANDA ESPONTÁNEA: no hay turnos programados.
6. Mantené un tono claro, amable y simple. El paciente puede no tener conocimientos médicos.
6. Cuando el paciente haya resuelto todas sus dudas, preguntale si desea recibir un resumen de las condiciones por correo electrónico.
7. Si el paciente pide el resumen, generalo en formato claro y estructurado (detallado abajo).
8. Si se proporcionan múltiples análisis, combina las instrucciones (por ejemplo, si uno requiere 8 horas de ayuno y otro 12 horas, indícale la condición más estricta, que es 12 horas).

FORMATO DEL RESUMEN (cuando el paciente lo solicite):
---
RESUMEN DE PREPARACIÓN PARA TU ANÁLISIS
Hospital Universitario UNCuyo - Servicio de Análisis Clínicos

- **Análisis:** [nombres de los análisis]
- **Horario de extracción:** [horario unificado]
- **Días disponibles:** [si aplica]
- **Tipo de muestra:** [tipo]
- **Ayuno:** [sí/no y detalles combinados]
- **Duración del estudio:** [si aplica]

Condiciones previas:
[listado de indicaciones]

[Insumos a conseguir antes de ir, si aplica]

Ante cualquier duda: {email} {telefono}
---

Cuando incluyas el resumen en tu respuesta, marcalo con las etiquetas <resumen> y </resumen>.
PROMPT;

    private string $promptSinAnalisis = <<<'PROMPT'
Eres el asistente virtual del Servicio de Análisis Clínicos del Hospital Universitario UNCuyo.
El paciente te está haciendo una pregunta, pero aún no te ha dicho qué análisis se va a realizar, o no has podido identificar ninguno.

INFORMACIÓN GENERAL DEL LABORATORIO:
- Email: {email}
- Teléfono: {telefono}
- Horario de atención: {horario}
- Turnos: Se atiende por DEMANDA ESPONTÁNEA (por orden de llegada), no se dan turnos programados.

TUS TAREAS:
1. Responder de forma amable cualquier pregunta general (horarios, ubicación, contacto, modalidad de atención) basándote ÚNICAMENTE en la Información General proporcionada arriba.
2. Si el paciente te pregunta por indicaciones médicas, requerimientos de ayuno o preparaciones, EXPLÍCALE que las instrucciones varían mucho dependiendo del análisis.
3. PÍDELE que te escriba el nombre de los estudios que el médico le solicitó, o que te envíe una FOTO de la orden médica para que puedas darle las indicaciones exactas.
4. Nunca intentes adivinar o dar indicaciones generales de ayuno sin saber qué análisis se realizará.
PROMPT;

    public function __construct(private KnowledgeService $knowledge) {}

    /**
     * Genera la respuesta del chatbot.
     * Devuelve array con: respuesta, listo_para_enviar, resumen.
     */
    public function generarRespuesta(string $mensaje, array $historial, ?array $analisisIds, ?string $imagenBase64 = null, ?string $mimeType = null): array
    {
        $contacto = $this->knowledge->obtenerContacto();
        $email    = $contacto['email'] ?? 'informeslaboratorio.hu@gmail.com';
        $telefono = $contacto['telefono'] ?? 'consultar con el laboratorio';
        $horario  = $contacto['horario_atencion'] ?? 'Consultar con el laboratorio';

        if (!empty($analisisIds)) {
            $contexto = $this->knowledge->obtenerContextosAnalisis($analisisIds);
            $system = str_replace(
                ['{email}', '{telefono}', '{horario}'],
                [$email, $telefono, $horario],
                $this->promptBase
            );
            $system .= "\n\nINFORMACIÓN OFICIAL DE LOS ANÁLISIS SOLICITADOS:\n{$contexto}";
        } else {
            $system = str_replace(
                ['{email}', '{telefono}', '{horario}'],
                [$email, $telefono, $horario],
                $this->promptSinAnalisis
            );
            if ($imagenBase64) {
                $system .= "\n\n[ATENCIÓN: El paciente acaba de enviar una imagen adjunta. Revisa si es una receta médica que no corresponde a análisis clínicos (por ejemplo, cardiología, diagnóstico por imagen, etc.) y explícaselo amablemente, o si es ilegible, pídele que la aclare.]";
            }
        }

        $llm = LlmProviderFactory::make();
        $texto = $llm->generarRespuestaChat($system, $historial, $mensaje, $imagenBase64, $mimeType);

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
            'respuesta'         => trim($texto),
            'listo_para_enviar' => $listoParaEnviar,
            'resumen'           => $resumen,
        ];
    }
}