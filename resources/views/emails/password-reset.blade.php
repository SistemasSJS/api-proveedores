<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperación de Contraseña</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f4f4f4;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        .header {
            background: linear-gradient(135deg, #FFC107 0%, #FFD54F 100%);
            padding: 40px 20px;
            text-align: center;
        }
        .logo {
            max-width: 100px;
            height: auto;
            margin-bottom: 15px;
        }
        .header-title {
            color: #000000;
            font-size: 28px;
            font-weight: 600;
            margin: 0;
        }
        .content {
            padding: 40px 30px;
        }
        .welcome-text {
            font-size: 18px;
            color: #2c3e50;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .message {
            font-size: 16px;
            color: #555555;
            margin-bottom: 30px;
            line-height: 1.8;
        }
        .cta-container {
            text-align: center;
            margin: 35px 0;
        }
        .cta-button {
            display: inline-block;
            padding: 16px 40px;
            background: linear-gradient(135deg, #FFC107 0%, #FFD54F 100%);
            color: #000000 !important;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.4);
        }
        .divider {
            height: 1px;
            background: linear-gradient(to right, transparent, #e0e0e0, transparent);
            margin: 30px 0;
        }
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #FFC107;
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .info-box p {
            margin: 0;
            color: #666666;
            font-size: 14px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 30px 20px;
            text-align: center;
            border-top: 1px solid #e0e0e0;
        }
        .footer-text {
            color: #999999;
            font-size: 13px;
            margin: 5px 0;
        }
        .footer-link {
            color: #FFC107;
            text-decoration: none;
        }
        .security-note {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            padding: 15px;
            border-radius: 6px;
            margin-top: 25px;
        }
        .security-note p {
            color: #856404;
            font-size: 13px;
            margin: 0;
        }
        @media only screen and (max-width: 600px) {
            .content {
                padding: 30px 20px;
            }
            .header-title {
                font-size: 24px;
            }
            .cta-button {
                padding: 14px 30px;
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header con logo -->
        <div class="header">
            <img src="{{ config('app.url') }}/assets/logo-icon-384x384.png" alt="SJS Construcciones Logo" class="logo">
            <h1 class="header-title">Recuperación de Contraseña</h1>
        </div>

        <!-- Contenido principal -->
        <div class="content">
            @if($userName)
            <p class="welcome-text">¡Hola, {{ $userName }}!</p>
            @else
            <p class="welcome-text">¡Hola!</p>
            @endif
            
            <p class="message">
                Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en SJS Construcciones.
            </p>

            <p class="message">
                Si realizaste esta solicitud, haz clic en el botón de abajo para crear una nueva contraseña:
            </p>

            <!-- Botón de acción -->
            <div class="cta-container">
                <a href="{{ $url }}" class="cta-button">
                    Restablecer mi contraseña
                </a>
            </div>

            <div class="divider"></div>

            <!-- Información adicional -->
            <div class="info-box">
                <p>
                    <strong>Por tu seguridad:</strong><br>
                    Este enlace es válido por <strong>60 minutos</strong> y solo puede ser utilizado una vez.
                </p>
            </div>

            <!-- Nota de seguridad -->
            <div class="security-note">
                <p>
                    <strong>⚠️ Importante:</strong> Si no solicitaste restablecer tu contraseña, 
                    ignora este correo. Tu contraseña actual permanecerá sin cambios y tu cuenta estará segura.
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p class="footer-text">
                © {{ date('Y') }} SJS Construcciones. Todos los derechos reservados.
            </p>
            <p class="footer-text">
                ¿Necesitas ayuda? <a href="mailto:soporte@sjsconstrucciones.com" class="footer-link">Contáctanos</a>
            </p>
        </div>
    </div>
</body>
</html>
