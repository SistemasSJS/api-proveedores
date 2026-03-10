<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Presupuesto enviado</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; background: #f8f9fa; }
    .email-container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    .header { background: linear-gradient(135deg, #2196F3, #42A5F5); color: #fff; padding: 30px 20px; text-align: center; }
    .content { padding: 30px 20px; }
    .greeting { font-size: 18px; font-weight: 500; margin-bottom: 20px; color: #2c3e50; }
    .success-box { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0; border-radius: 4px; color: #155724; }
    .action-button { display: inline-block; background: #2196F3; color: #fff; padding: 15px 30px; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 20px 0; }
    .footer { background: #343a40; color: #fff; padding: 15px; text-align: center; font-size: 12px; }
  </style>
</head>
<body>
  <div class="email-container">
    <div class="header">
      <h1>Presupuesto enviado</h1>
      <p>Sistema de Gestión de Proveedores</p>
    </div>
    <div class="content">
      <div class="greeting">Hola {{ $notifiable->name }},</div>
      <div class="success-box">
        El presupuesto #{{ $presupuesto->numero_presupuesto }} fue enviado correctamente a {{ $presupuesto->empresa_receptora_empresa ?? $presupuesto->empresa_receptora_nombre ?? 'el cliente' }}.
      </div>
      <p><a href="{{ $urlDetalle }}" class="action-button">Ver detalle del presupuesto</a></p>
    </div>
    <div class="footer">Mensaje automático - Sistema de Gestión de Proveedores</div>
  </div>
</body>
</html>
