<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Presupuesto recibido</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; background-color: #f8f9fa; }
    .email-container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    .header { background: linear-gradient(135deg, #2196F3 0%, #42A5F5 100%); color: #fff; padding: 30px 20px; text-align: center; }
    .logo { max-width: 80px; height: auto; margin-bottom: 15px; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2)); }
    .header h1 { font-size: 24px; font-weight: 600; margin-bottom: 5px; }
    .header p { font-size: 14px; opacity: 0.9; }
    .content { padding: 30px 20px; }
    .greeting { font-size: 18px; font-weight: 500; margin-bottom: 20px; color: #2c3e50; }
    .intro-text { font-size: 16px; margin-bottom: 25px; color: #555; }
    .presupuesto-card { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 20px; margin: 25px 0; }
    .presupuesto-header { margin-bottom: 15px; padding-bottom: 15px; border-bottom: 2px solid #2196F3; }
    .presupuesto-folio { font-size: 20px; font-weight: 600; color: #2196F3; }
    .presupuesto-total { font-size: 18px; font-weight: 600; color: #28a745; }
    .detail-item { margin: 8px 0; }
    .detail-label { font-weight: 600; color: #495057; margin-right: 8px; }
    .detail-value { color: #6c757d; }
    .action-button { display: inline-block; background: linear-gradient(135deg, #2196F3 0%, #42A5F5 100%); color: #fff; padding: 15px 30px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px; text-align: center; margin: 25px 0; }
    .info-box { background: #e3f2fd; border-left: 4px solid #2196F3; padding: 15px; margin: 20px 0; border-radius: 0 6px 6px 0; }
    .footer-text { font-size: 14px; color: #6c757d; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e9ecef; }
    .footer { background: #343a40; color: #fff; padding: 20px; text-align: center; font-size: 12px; }
  </style>
</head>
<body>
  <div class="email-container">
    <div class="header">
      <img src="{{ config('app.url') }}/assets/logos/logo-gestionpro.png" alt="GestiónPro" class="logo">
      <h1>Presupuesto recibido</h1>
      <p>Sistema de Gestión de Proveedores</p>
    </div>
    <div class="content">
      <div class="greeting">Hola {{ $nombreReceptor }},</div>
      <div class="intro-text">
        {{ $presupuesto->proveedor?->nombre_comercial ?? $presupuesto->proveedor?->razon_social ?? 'Un proveedor' }} te ha enviado un presupuesto para tu revisión.
      </div>
      <div class="presupuesto-card">
        <div class="presupuesto-header">
          <div class="presupuesto-folio">Presupuesto #{{ $presupuesto->numero_presupuesto }}</div>
          <div class="presupuesto-total" style="margin-top: 8px;">Total: ${{ number_format($presupuesto->total, 2) }}</div>
        </div>
        <div class="detail-item">
          <span class="detail-label">Concepto:</span>
          <span class="detail-value">{{ Str::limit($presupuesto->concepto_general, 80) }}</span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Fecha emisión:</span>
          <span class="detail-value">{{ $presupuesto->fecha_emision?->format('d/m/Y') }}</span>
        </div>
        @if($presupuesto->fecha_vencimiento)
        <div class="detail-item">
          <span class="detail-label">Vigencia hasta:</span>
          <span class="detail-value">{{ $presupuesto->fecha_vencimiento->format('d/m/Y') }}</span>
        </div>
        @endif
      </div>
      <div style="text-align:left;margin:20px 0;">
        @include('emails.presupuesto.partials.detalles-presupuesto', ['presupuesto' => $presupuesto])
      </div>
      <div class="info-box">
        <strong>En este correo va adjunto el PDF del presupuesto.</strong> También puedes verlo, compartirlo, aceptarlo o rechazarlo desde el enlace seguro:
      </div>
      <div style="text-align: center;">
        <a href="{{ $enlacePublico }}" class="action-button">Ver presupuesto</a>
      </div>
      <div class="footer-text">
        <p>Este enlace es único y seguro. No compartas este correo con terceros si contiene información confidencial.</p>
        <p style="margin-top: 15px;">Si tienes dudas, contacta directamente al proveedor.</p>
      </div>
    </div>
    <div class="footer">
      <p>Sistema de Gestión de Proveedores - Mensaje automático, no responder.</p>
    </div>
  </div>
</body>
</html>
