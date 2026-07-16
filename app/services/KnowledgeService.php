<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class KnowledgeService
{
    private const RUTA_JSON = 'analisis_laboratorio.json';

    private ?array $base = null;

    /**
     * Carga (con cache en memoria por request) el JSON completo de análisis.
     */
    private function cargarBase(): array
    {
        if ($this->base === null) {
            $this->base = json_decode(
                Storage::get(self::RUTA_JSON),
                associative: true
            );
        }

        return $this->base;
    }

    /**
     * Devuelve el array de un análisis dado su id, o null si no existe.
     */
    public function obtenerAnalisisPorId(string $analisisId): ?array
    {
        $base = $this->cargarBase();

        foreach ($base['analisis'] as $analisis) {
            if ($analisis['id'] === $analisisId) {
                return $analisis;
            }
        }

        return null;
    }

    /**
     * Devuelve un texto compacto con id, nombre_oficial, alias y palabras_clave
     * de todos los análisis. Se usa en el prompt de identificación para que el
     * LLM pueda matchear sin recibir el JSON completo.
     */
    public function catalogoParaIdentificacion(): string
    {
        $base = $this->cargarBase();

        $bloques = array_map(function (array $a) {
            $alias    = implode(', ', $a['alias'] ?? []);
            $palabras = implode(', ', $a['palabras_clave'] ?? []);

            return "ID: {$a['id']}\n"
                . "Nombre oficial: {$a['nombre_oficial']}\n"
                . "Alias: {$alias}\n"
                . "Palabras clave: {$palabras}\n";
        }, $base['analisis']);

        return implode("\n---\n", $bloques);
    }

    /**
     * Devuelve el contexto en texto del análisis identificado,
     * formateado para inyectarlo en el prompt del chatbot.
     */
    public function obtenerContextoAnalisis(string $analisisId): ?string
    {
        $a = $this->obtenerAnalisisPorId($analisisId);

        if (!$a) {
            return null;
        }

        $ayuno = $a['ayuno'] ?? [];
        $ayunoTexto = 'No requiere ayuno.';
        if (!empty($ayuno['requiere'])) {
            $ayunoTexto = !empty($ayuno['horas'])
                ? "Requiere ayuno de {$ayuno['horas']} horas."
                : trim('Requiere ayuno. ' . ($ayuno['aclaracion'] ?? ''));
        }

        $insumo = $a['requiere_insumo_externo'] ?? [];
        $insumoTexto = !empty($insumo['requiere'])
            ? 'REQUIERE INSUMO EXTERNO: ' . ($insumo['descripcion'] ?? '')
            : '';

        $duracionTexto = !empty($a['duracion_estudio'])
            ? "Duración del estudio: {$a['duracion_estudio']}"
            : '';

        $diasTexto = !empty($a['dias_disponibles'])
            ? 'Días disponibles: ' . implode(', ', $a['dias_disponibles']) . '.'
            : '';

        $instruccionesTexto = '';
        if (!empty($a['instrucciones_por_paciente'])) {
            $lineas = [];
            foreach ($a['instrucciones_por_paciente'] as $tipo => $instruccion) {
                if ($instruccion) {
                    $tipoLegible = ucfirst(str_replace('_', ' ', $tipo));
                    $lineas[] = "  - {$tipoLegible}: {$instruccion}";
                }
            }
            if ($lineas) {
                $instruccionesTexto = "Instrucciones según tipo de paciente:\n" . implode("\n", $lineas);
            }
        }

        $partes = [
            "=== {$a['nombre_oficial']} ===",
            "Horario de extracción: " . ($a['horario_extraccion'] ?? 'Consultar con el laboratorio.'),
            $diasTexto,
            "Tipo de muestra: " . ($a['tipo_muestra'] ?? 'Consultar con el laboratorio.'),
            $ayunoTexto,
            $duracionTexto,
            "Indicaciones generales: " . ($a['indicaciones_generales'] ?? ''),
            !empty($a['indicaciones_especificas'])
                ? "Indicaciones específicas: {$a['indicaciones_especificas']}"
                : '',
            $instruccionesTexto,
            $insumoTexto,
        ];

        return implode("\n", array_filter($partes, fn ($p) => $p !== ''));
    }

    /**
     * Devuelve los datos de contacto globales del laboratorio.
     */
    public function obtenerContacto(): array
    {
        return $this->cargarBase()['contacto'] ?? [];
    }
}