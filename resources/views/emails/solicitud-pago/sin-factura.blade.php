<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Solicitud de Pago Sin Factura</title>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f8f9fa;
      margin: 0;
      padding: 0;
    }

    .email-container {
      max-width: 600px;
      margin: auto;
      background: #ffffff;
      border-radius: 8px;
      overflow: hidden;
    }

    .header {
      background: linear-gradient(135deg, #ff9800, #ffc107);
      color: #ffffff;
      text-align: center;
      padding: 30px 20px;
    }

    .header h1 {
      margin: 0;
    }

    .content {
      padding: 30px 20px;
    }

    .alert-box {
      background-color: #fff3cd;
      border-left: 4px solid #ff9800;
      padding: 15px;
      margin: 20px 0;
      border-radius: 4px;
    }

    .action-button {
      display: inline-block;
      background: #ff9800;
      color: #ffffff;
      padding: 15px 30px;
      text-decoration: none;
      border-radius: 6px;
      font-weight: 600;
    }

    .footer {
      background-color: #343a40;
      color: #ffffff;
      text-align: center;
      padding: 15px;
      font-size: 12px;
    }
  </style>
</head>

<body>

  <div class="email-container">
    <div class="header">
      <h1>🧾 Solicitud de Pago Sin Factura</h1>
      <p>Sistema de Gestión de Proveedores</p>
    </div>

    <div class="content">
      <p>Hola <strong>{{ $notifiable->name }}</strong>,</p>

      <div class="alert-box">
        Tu solicitud de pago <strong>#{{ $solicitudPagoFolio }}</strong> aún no cuenta con una factura asociada.
      </div>

      <p>
        Para continuar con el proceso de pago, es necesario que subas la factura correspondiente
        en el sistema.
      </p>

      <p style="text-align: center; margin: 30px 0;">
        <a href="{{ $urlSolicitud }}" class="action-button">
          Subir Factura
        </a>
      </p>

      <p>
        Si tienes dudas o consideras que esto es un error, por favor contáctanos.
      </p>
    </div>

    <div class="footer">
      © {{ date('Y') }} {{ config('app.name') }} · Mensaje automático
    </div>
  </div>

</body>

</html>