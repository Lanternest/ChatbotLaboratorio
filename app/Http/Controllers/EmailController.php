<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use App\Mail\AnalisisResumen;

class EmailController extends Controller
{
    public function sendEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email_paciente'  => 'required|email',
            'resumen'         => 'required|string',
            'analisis_nombre' => 'required|string',
        ]);

        try {
            Mail::to($request->input('email_paciente'))
                ->send(new AnalisisResumen(
                    resumen: $request->input('resumen'),
                    analisisNombre: $request->input('analisis_nombre')
                ));

            return response()->json([
                'enviado' => true,
                'mensaje' => 'El resumen fue enviado correctamente a tu correo electrónico.',
            ]);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'enviado' => false,
                'mensaje' => 'Hubo un problema al enviar el correo. Por favor comunicate con el laboratorio: informeslaboratorio.hu@gmail.com',
            ], 500);
        }
    }
}