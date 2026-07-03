import os
import anthropic
from app.services.conocimiento import obtener_catalogo_para_identificacion

_client = anthropic.Anthropic(api_key=os.getenv("ANTHROPIC_API_KEY"))

PROMPT_IDENTIFICACION = """Eres un asistente de identificación de análisis clínicos del laboratorio del Hospital Universitario UNCuyo.

Tu única tarea es determinar a qué análisis se refiere el paciente, basándote EXCLUSIVAMENTE en el catálogo que se te proporciona a continuación.

CATÁLOGO DE ANÁLISIS DISPONIBLES:
{catalogo}

INSTRUCCIONES:
- Analiza el mensaje del paciente y el historial de la conversación.
- Si podés identificar con certeza el análisis, responde ÚNICAMENTE con el ID exacto del análisis (por ejemplo: hemograma).
- Si NO podés identificar el análisis con certeza, responde ÚNICAMENTE con la palabra: null
- No des explicaciones. No saludes. No hagas preguntas. Solo devuelve el ID o null.
- Nunca inventes IDs que no estén en el catálogo.

Historial de conversación:
{historial}

Último mensaje del paciente: {mensaje}

Responde solo con el ID del análisis o null:"""


def identificar_analisis(mensaje: str, historial: list) -> str | None:
    """
    Intenta identificar el análisis clínico mencionado por el paciente.
    Devuelve el id del análisis o None si no pudo identificarlo.
    """
    catalogo = obtener_catalogo_para_identificacion()
    historial_texto = "\n".join(
        f"{m['role'].upper()}: {m['content']}" for m in historial
    ) if historial else "(sin historial previo)"

    prompt = PROMPT_IDENTIFICACION.format(
        catalogo=catalogo,
        historial=historial_texto,
        mensaje=mensaje
    )

    respuesta = _client.messages.create(
        model="claude-sonnet-4-6",
        max_tokens=50,
        messages=[{"role": "user", "content": prompt}]
    )

    resultado = respuesta.content[0].text.strip().lower()

    if resultado == "null" or not resultado:
        return None
    return resultado