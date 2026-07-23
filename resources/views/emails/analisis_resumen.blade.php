<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Indicaciones Médicas - Hospital Universitario</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #222222;
            line-height: 1.8;
            margin: 0;
            padding: 20px;
            background-color: #f0f4f8;
            font-size: 18px; /* Letra más grande para facilitar la lectura */
        }
        .container {
            max-width: 650px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border-top: 8px solid #005aa3; /* Azul hospitalario */
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 25px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #005aa3;
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .header p {
            margin: 10px 0 0 0;
            color: #64748b;
            font-size: 18px;
        }
        .content {
            font-size: 18px;
        }
        .content h1, .content h2, .content h3 {
            color: #005aa3;
            margin-top: 30px;
            margin-bottom: 15px;
        }
        .content p {
            margin-bottom: 20px;
        }
        .content ul {
            background-color: #f8fafc;
            padding: 20px 20px 20px 40px;
            border-radius: 8px;
            border-left: 4px solid #0284c7;
            margin-bottom: 25px;
        }
        .content li {
            margin-bottom: 12px;
        }
        .content strong {
            color: #0f172a;
            font-weight: 700;
            background-color: #fef08a; /* Resaltador suave */
            padding: 2px 4px;
            border-radius: 4px;
        }
        .highlight-box {
            background-color: #eff6ff;
            border: 2px solid #bfdbfe;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
            text-align: center;
        }
        .highlight-box h3 {
            margin-top: 0;
            color: #1e3a8a;
        }
        .footer {
            margin-top: 40px;
            padding-top: 25px;
            border-top: 1px solid #e2e8f0;
            font-size: 14px;
            color: #64748b;
            text-align: center;
            line-height: 1.5;
        }
        .alert-text {
            color: #b91c1c;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Indicaciones para tu Análisis</h1>
            <p><strong>Laboratorio de Análisis Clínicos</strong><br>Hospital Universitario UNCuyo</p>
        </div>
        
        <div class="content">
            <p>Hola. A continuación te enviamos de forma detallada las indicaciones que debes seguir para realizarte tus estudios correctamente.</p>
            
            <!-- Transformamos el Markdown de la IA (negritas, listas) a HTML real para que se vea hermoso -->
            {!! \Illuminate\Support\Str::markdown($resumen) !!}
            
        </div>
        
        <div class="highlight-box">
            <h3>¿Tienes alguna duda?</h3>
            <p>No dudes en comunicarte con nosotros antes de asistir:</p>
            <p style="font-size: 20px; font-weight: bold; color: #005aa3; margin-bottom: 0;">informeslaboratorio.hu@gmail.com</p>
        </div>
        
        <div class="footer">
            <p>Este es un mensaje enviado automáticamente por el Asistente Virtual del Hospital Universitario.</p>
            <p>Por favor, revisa bien los horarios de atención y recuerda que la atención es por orden de llegada.</p>
        </div>
    </div>
</body>
</html>
