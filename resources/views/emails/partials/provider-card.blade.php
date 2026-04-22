@php
    $proveedorAlias = $proveedorAlias
        ?? ($proveedor->nombre_comercial ?? null);
    $proveedorRazonSocial = $proveedorRazonSocial
        ?? ($proveedor->razon_social ?? null);
    $proveedorTelefono = $proveedorTelefono
        ?? ($proveedor->telefono ?? null);
    $proveedorEmail = $proveedorEmail
        ?? ($proveedor->email ?? null);
    $proveedorWeb = $proveedorWeb
        ?? ($proveedor->pagina_web ?? null);
    $proveedorLogo = $proveedorLogo
        ?? ($proveedor->logo ?? null);

    $telefonoFormateado = $proveedorTelefono ? '+52 '.$proveedorTelefono : 'No disponible';
@endphp

<div style="border:1px solid #dbe3ee;border-radius:14px;background:#ffffff;margin:16px 0;overflow:hidden;box-shadow:0 4px 14px rgba(15,76,129,0.08);">
  <!-- <div style="background:linear-gradient(135deg,#eff6ff 0%,#dbeafe 100%);color:#0f4c81;font-weight:700;padding:10px 14px;font-size:14px;">Ficha del proveedor</div> -->
  <div style="padding:14px;">
    <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;">
      <tr>
        @if(!empty($proveedorLogo))
          <td style="width:92px;vertical-align:top;padding-right:12px;">
            <div style="width:84px;height:84px;border-radius:12px;background:#f8fafc;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;">
              <img src="{{ $proveedorLogo }}" alt="Logo proveedor" style="max-width:70px;max-height:56px;height:auto;width:auto;">
            </div>
          </td>
        @endif
        <td style="vertical-align:top;">
          <div style="font-size:18px;color:#0f172a;font-weight:700;line-height:1.25;">{{ $proveedorAlias ?: 'Proveedor' }}</div>
          <div style="font-size:13px;color:#475569;margin-top:4px;">{{ $proveedorRazonSocial ?: 'Razón social no disponible' }}</div>

          <div style="margin-top:10px;font-size:13px;color:#334155;line-height:1.55;">
            <div><strong>Teléfono:</strong> {{ $telefonoFormateado }}</div>
            <div><strong>Email:</strong> {{ $proveedorEmail ?: 'No disponible' }}</div>
            <div>
              <strong>Página web:</strong>
              @if($proveedorWeb)
                <a href="{{ str_starts_with($proveedorWeb, 'http') ? $proveedorWeb : 'https://'.$proveedorWeb }}" style="color:#1f6fb2;text-decoration:none;">{{ $proveedorWeb }}</a>
              @else
                No disponible
              @endif
            </div>
          </div>
        </td>
      </tr>
    </table>
  </div>
</div>
