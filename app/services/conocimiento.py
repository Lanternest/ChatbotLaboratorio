import json
import os
from typing import Optional

_BASE_PATH = os.path.join(os.path.dirname(__file__), "../../analisis_laboratorio.json")

def cargar_base() -> dict:
    """Carga el JSON completo de análisis."""
    with open(_BASE_PATH, encoding="utf-8") as f:
        return json.load(f)

def obtener_analisis_por_id(analisis_id: str) -> Optional[dict]:
    """Devuelve el dict de un análisis dado su id, o None si no existe."""
    base = cargar_base()
    for analisis in base["analisis"]:
        if analisis["id"] == analisis_id:
            return analisis
    return None

def obtener_catalogo_para_identificacion() -> str:
    """
    Devuelve un texto compacto con id, nombre_oficial, alias y palabras_clave
    de todos los análisis. Se usa en el prompt de identificación para que el
    LLM pueda matchear sin recibir el JSON completo.
    """
    base = cargar_base()
    lineas = []
    for a in base["analisis"]:
        alias = ", ".join(a.get("alias", []))
        palabras = ", ".join(a.get("palabras_clave", []))
        lineas.append(
            f'ID: {a["id"]}\n'
            f'Nombre oficial: {a["nombre_oficial"]}\n'
            f'Alias: {alias}\n'
            f'Palabras clave: {palabras}\n'
        )
    return "\n---\n".join(lineas)

def obtener_contexto_analisis(analisis_id: str) -> Optional[str]:
    """
    Devuelve el contexto en texto del análisis identificado,
    formateado para inyectarlo en el prompt del chatbot.
    """
    a = obtener_analisis_por_id(analisis_id)
    if not a:
        return None

    ayuno = a.get("ayuno", {})
    ayuno_texto = "No requiere ayuno."
    if ayuno.get("requiere"):
        if ayuno.get("horas"):
            ayuno_texto = f"Requiere ayuno de {ayuno['horas']} horas."
        else:
            ayuno_texto = f"Requiere ayuno. {ayuno.get('aclaracion', '')}".strip()

    insumo = a.get("requiere_insumo_externo", {})
    insumo_texto = ""
    if insumo.get("requiere"):
        insumo_texto = f"REQUIERE INSUMO EXTERNO: {insumo.get('descripcion', '')}"

    duracion = a.get("duracion_estudio")
    duracion_texto = f"Duración del estudio: {duracion}" if duracion else ""

    dias = a.get("dias_disponibles")
    dias_texto = f"Días disponibles: {', '.join(dias)}." if dias else ""

    instrucciones_por_tipo = a.get("instrucciones_por_paciente")
    instrucciones_texto = ""
    if instrucciones_por_tipo:
        lineas = []
        for tipo, instruccion in instrucciones_por_tipo.items():
            if instruccion:
                lineas.append(f"  - {tipo.replace('_', ' ').capitalize()}: {instruccion}")
        if lineas:
            instrucciones_texto = "Instrucciones según tipo de paciente:\n" + "\n".join(lineas)

    partes = [
        f"=== {a['nombre_oficial']} ===",
        f"Horario de extracción: {a.get('horario_extraccion', 'Consultar con el laboratorio.')}",
        dias_texto,
        f"Tipo de muestra: {a.get('tipo_muestra', 'Consultar con el laboratorio.')}",
        ayuno_texto,
        duracion_texto,
        f"Indicaciones generales: {a.get('indicaciones_generales', '')}",
        f"Indicaciones específicas: {a.get('indicaciones_especificas', '')}" if a.get('indicaciones_especificas') else "",
        instrucciones_texto,
        insumo_texto,
    ]

    return "\n".join(p for p in partes if p)

def obtener_contacto() -> dict:
    """Devuelve los datos de contacto globales del laboratorio."""
    return cargar_base().get("contacto", {})