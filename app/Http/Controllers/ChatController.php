<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\IdentifierService;
use App\Services\ChatbotService;

class ChatController extends Controller
{
    public function __construct(
        private IdentifierService $identifier,
        private ChatbotService $chatbot
    ) {}

    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'mensaje'               => 'nullable|string|max:2000',
            'imagen'                => 'nullable|image|max:5120', // Hasta 5MB
            'historial'             => 'array',
            'historial.*.role'      => 'required|in:user,assistant',
            'historial.*.content'   => 'required|string',
            'analisis_identificado' => 'nullable|array', // Ahora es un arreglo de IDs
        ]);

        $mensaje    = (string) $request->input('mensaje', '');
        $historial  = $request->input('historial', []);
        $analisisId = $request->input('analisis_identificado', []);

        $imagenBase64 = null;
        $mimeType = null;

        if ($request->hasFile('imagen')) {
            $imagen = $request->file('imagen');
            $imagenBase64 = base64_encode(file_get_contents($imagen->getRealPath()));
            $mimeType = $imagen->getMimeType();
        }

        try {
            // Siempre intentamos identificar si hay nuevos análisis mencionados en el mensaje
            // Si el mensaje está vacío pero hay imagen, pasamos un prompt por defecto
            $promptMensaje = empty($mensaje) && $imagenBase64 
                ? "Aquí tienes una orden médica. Por favor, dime qué análisis están solicitados." 
                : $mensaje;
                
            $nuevosIds = $this->identifier->identificar($promptMensaje, $historial, $imagenBase64, $mimeType);
            
            // Unimos los nuevos IDs con los que ya teníamos y eliminamos duplicados
            $analisisId = array_unique(array_merge($analisisId, $nuevosIds));
            $resultado = $this->chatbot->generarRespuesta($mensaje, $historial, $analisisId, $imagenBase64, $mimeType);

            return response()->json([
                'respuesta'              => $resultado['respuesta'],
                'analisis_identificado'  => array_values($analisisId), // Arreglo limpio sin claves raras
                'listo_para_enviar'      => $resultado['listo_para_enviar'],
                'resumen'                => $resultado['resumen'],
            ]);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            $respuestaAmigable = "⚠️ Ocurrió un error al procesar tu solicitud.";
            
            if (str_contains($msg, '429') || str_contains($msg, 'RESOURCE_EXHAUSTED') || str_contains($msg, 'quota')) {
                $respuestaAmigable = "⏳ **El sistema está recibiendo demasiadas consultas en este momento.**\nPor favor, espera unos 30 segundos y vuelve a enviar tu mensaje.";
            } elseif (str_contains($msg, 'API Key no está configurada') || str_contains($msg, 'invalid API key')) {
                $respuestaAmigable = "⚠️ Error del sistema: La API Key no es válida o falta en el archivo .env.";
            }

            return response()->json([
                'respuesta' => $respuestaAmigable,
                'analisis_identificado' => array_values($analisisId),
                'listo_para_enviar' => false,
                'resumen' => null,
            ]);
        }
    }
}