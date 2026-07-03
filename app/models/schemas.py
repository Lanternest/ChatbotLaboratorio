from pydantic import BaseModel, EmailStr
from typing import Optional


class Mensaje(BaseModel):
    role: str       # "user" o "assistant"
    content: str


class ChatRequest(BaseModel):
    mensaje: str
    historial: list[Mensaje] = []
    analisis_identificado: Optional[str] = None  # id del análisis si ya fue identificado


class ChatResponse(BaseModel):
    respuesta: str
    analisis_identificado: Optional[str] = None  # id si el bot lo identificó en este turno
    listo_para_enviar: bool = False              # True cuando el bot sugiere enviar el resumen
    resumen: Optional[str] = None               # Resumen en texto plano si listo_para_enviar=True


class EmailRequest(BaseModel):
    email_paciente: EmailStr
    resumen: str
    analisis_nombre: str


class EmailResponse(BaseModel):
    enviado: bool
    mensaje: str