from fastapi import APIRouter
from app.models.schemas import EmailRequest, EmailResponse
from app.services.email_service import enviar_resumen

router = APIRouter()


@router.post("/send-email", response_model=EmailResponse)
def send_email(request: EmailRequest):
    """
    Envía el resumen de preparación al correo del paciente.
    El frontend llama a este endpoint solo cuando el paciente confirmó
    su email y aceptó recibir el resumen.
    """
    enviado = enviar_resumen(
        email_paciente=request.email_paciente,
        resumen=request.resumen,
        analisis_nombre=request.analisis_nombre
    )

    if enviado:
        return EmailResponse(
            enviado=True,
            mensaje="El resumen fue enviado correctamente a tu correo electrónico."
        )
    else:
        return EmailResponse(
            enviado=False,
            mensaje="Hubo un problema al enviar el correo. Por favor comunicate con el laboratorio: informeslaboratorio.hu@gmail.com"
        )