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
            'mensaje'              => 'required|string|max:2000',
            'historial'            => 'array',
            'historial.*.role'     => 'required|in:user,assistant',
            'historial.*.content'  => 'required|string',
            'analisis_identificado' => 'nullable|string',
        ]);

        $mensaje   = $request->input('mensaje');
        $historial = $request->input('historial', []);
        $analisisId = $request->input('analisis_identificado');

        // Solo intentar identificar si aún no tenemos el análisis
        if (!$analisisId) {
            $analisisId = $this->identifier->identificar($mensaje, $historial);
        }

        $resultado = $this->chatbot->generarRespuesta($mensaje, $historial, $analisisId);

        return response()->json([
            'respuesta'              => $resultado['respuesta'],
            'analisis_identificado'  => $analisisId,
            'listo_para_enviar'      => $resultado['listo_para_enviar'],
            'resumen'                => $resultado['resumen'],
        ]);
    }
}