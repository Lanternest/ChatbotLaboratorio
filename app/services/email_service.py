import os
import resend

resend.api_key = os.getenv("RESEND_API_KEY")

REMITENTE = "Laboratorio HU UNCuyo <laboratorio@hospital.uncuyo.edu.ar>"

TEMPLATE_HTML = """
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <style>
    body {{ font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }}
    .header {{ background-color: #003b7a; color: white; padding: 20px; border-radius: 8px 8px 0 0; }}
    .header h1 {{ margin: 0; font-size: 18px; }}
    .header p {{ margin: 4px 0 0; font-size: 13px; opacity: 0.85; }}
    .body {{ background: #f9f9f9; padding: 24px; border: 1px solid #ddd; }}
    .resumen {{ background: white; padding: 16px; border-left: 4px solid #003b7a; white-space: pre-line; font-size: 14px; line-height: 1.6; }}
    .footer {{ background: #eee; padding: 12px 20px; border-radius: 0 0 8px 8px; font-size: 12px; color: #666; }}
    .aviso {{ background: #fff3cd; border: 1px solid #ffc107; padding: 10px 14px; border-radius: 4px; margin-top: 16px; font-size: 13px; }}
  </style>
</head>
<body>
  <div class="header">
    <h1>🧪 Resumen de preparación para tu análisis</h1>
    <p>Hospital Universitario UNCuyo – Servicio de Análisis Clínicos</p>
  </div>
  <div class="body">
    <p>Hola,</p>
    <p>A continuación encontrás las condiciones que debés cumplir antes de realizarte el análisis <strong>{analisis_nombre}</strong>:</p>
    <div class="resumen">{resumen}</div>
    <div class="aviso">
      ⚠️ <strong>Recordá:</strong> El servicio funciona por demanda espontánea. No necesitás turno previo. 
      Simplemente presentate en el horario indicado.
    </div>
  </div>
  <div class="footer">
    Consultas: informeslaboratorio.hu@gmail.com | Este correo fue generado automáticamente por el asistente virtual del laboratorio.
  </div>
</body>
</html>
"""


def enviar_resumen(email_paciente: str, resumen: str, analisis_nombre: str) -> bool:
    """
    Envía el resumen de preparación al correo del paciente.
    Devuelve True si el envío fue exitoso.
    """
    try:
        params = resend.Emails.SendParams(
            from_=REMITENTE,
            to=[email_paciente],
            subject=f"Preparación para tu análisis: {analisis_nombre}",
            html=TEMPLATE_HTML.format(
                analisis_nombre=analisis_nombre,
                resumen=resumen
            )
        )
        respuesta = resend.Emails.send(params)
        return bool(respuesta.get("id"))
    except Exception as e:
        print(f"Error al enviar email: {e}")
        return False