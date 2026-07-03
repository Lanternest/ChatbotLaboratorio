from fastapi import APIRouter
from app.models.schemas import ChatRequest, ChatResponse
from app.services.identificador import identificar_analisis
from app.services.chatbot import generar_respuesta

router = APIRouter()


@router.post("/chat", response_model=ChatResponse)
def chat(request: ChatRequest):
    """
    Endpoint principal del chatbot.
    
    Flujo:
    1. Si ya hay un análisis identificado en la sesión, se usa directamente.
    2. Si no, se intenta identificar el análisis con el mensaje actual + historial.
    3. Se genera la respuesta del bot usando el contexto del análisis (o sin él).
    4. Si la respuesta incluye un resumen listo, se marca listo_para_enviar=True.
    """
    analisis_id = request.analisis_identificado

    # Solo intentar identificar si aún no tenemos el análisis
    if not analisis_id:
        analisis_id = identificar_analisis(
            mensaje=request.mensaje,
            historial=[m.model_dump() for m in request.historial]
        )

    resultado = generar_respuesta(
        mensaje=request.mensaje,
        historial=[m.model_dump() for m in request.historial],
        analisis_id=analisis_id
    )

    return ChatResponse(
        respuesta=resultado["respuesta"],
        analisis_identificado=analisis_id,
        listo_para_enviar=resultado["listo_para_enviar"],
        resumen=resultado["resumen"]
    )