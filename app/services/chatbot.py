import os
import anthropic
from app.services.conocimiento import (
    obtener_contexto_analisis,
    obtener_contacto,
)

_client = anthropic.Anthropic(api_key=os.getenv("ANTHROPIC_API_KEY"))

SYSTEM_PROMPT_BASE = """Eres el asistente virtual del Servicio de Análisis Clínicos del Hospital Universitario UNCuyo.
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
"""

SYSTEM_PROMPT_SIN_ANALISIS = """No has podido identificar el análisis del paciente.
Indicale amablemente que no encontraste información sobre ese análisis en tu base de datos y derivalo al laboratorio:
- Email: {email}
- Teléfono: {telefono}
No intentes adivinar ni inventar información."""


def generar_respuesta(
    mensaje: str,
    historial: list,
    analisis_id: str | None
) -> dict:
    """
    Genera la respuesta del chatbot.
    Devuelve dict con: respuesta, listo_para_enviar, resumen.
    """
    contacto = obtener_contacto()
    email = contacto.get("email", "informeslaboratorio.hu@gmail.com")
    telefono = contacto.get("telefono") or "consultar con el laboratorio"

    if analisis_id:
        contexto = obtener_contexto_analisis(analisis_id)
        system = SYSTEM_PROMPT_BASE.format(email=email, telefono=telefono)
        system += f"\n\nINFORMACIÓN OFICIAL DEL ANÁLISIS SOLICITADO:\n{contexto}"
    else:
        system = SYSTEM_PROMPT_SIN_ANALISIS.format(email=email, telefono=telefono)

    mensajes_api = [
        {"role": m["role"], "content": m["content"]}
        for m in historial
    ]
    mensajes_api.append({"role": "user", "content": mensaje})

    respuesta_api = _client.messages.create(
        model="claude-sonnet-4-6",
        max_tokens=1000,
        system=system,
        messages=mensajes_api
    )

    texto = respuesta_api.content[0].text.strip()

    # Detectar si la respuesta incluye un resumen listo para enviar
    resumen = None
    listo_para_enviar = False
    if "<resumen>" in texto and "</resumen>" in texto:
        inicio = texto.index("<resumen>") + len("<resumen>")
        fin = texto.index("</resumen>")
        resumen = texto[inicio:fin].strip()
        listo_para_enviar = True
        # Limpiar las etiquetas del texto que ve el paciente
        texto = texto.replace(f"<resumen>{texto[inicio:fin]}</resumen>", resumen)

    return {
        "respuesta": texto,
        "listo_para_enviar": listo_para_enviar,
        "resumen": resumen
    }