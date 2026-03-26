@php
  $emisorNombre = $presupuesto->proveedor?->nombre_comercial ?? $presupuesto->proveedor?->razon_social ?? '—';
  $moneda = $presupuesto->term_cond_moneda ?? 'MXN';
  $subtotal = number_format((float) $presupuesto->subtotal, 2);
  $total = number_format((float) $presupuesto->total, 2);
  $ivaPct = $presupuesto->iva_porcentaje;
  $conIva = $presupuesto->con_iva;
@endphp
<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;font-size:14px;color:#495057;">
  <tr>
    <td style="padding:6px 0;border-bottom:1px solid #e9ecef;"><strong>Proveedor emisor</strong></td>
    <td style="padding:6px 0;border-bottom:1px solid #e9ecef;text-align:right;">{{ $emisorNombre }}</td>
  </tr>
  <tr>
    <td style="padding:6px 0;border-bottom:1px solid #e9ecef;"><strong>Cliente / empresa</strong></td>
    <td style="padding:6px 0;border-bottom:1px solid #e9ecef;text-align:right;">{{ $presupuesto->empresa_receptora_empresa ?? $presupuesto->empresa_receptora_nombre ?? '—' }}</td>
  </tr>
  <tr>
    <td style="padding:6px 0;border-bottom:1px solid #e9ecef;"><strong>Contacto</strong></td>
    <td style="padding:6px 0;border-bottom:1px solid #e9ecef;text-align:right;">{{ $presupuesto->empresa_receptora_nombre ?? '—' }} @if($presupuesto->empresa_receptora_correo)<br><span style="font-size:13px;color:#6c757d;">{{ $presupuesto->empresa_receptora_correo }}</span>@endif</td>
  </tr>
  <tr>
    <td style="padding:6px 0;border-bottom:1px solid #e9ecef;"><strong>Concepto general</strong></td>
    <td style="padding:6px 0;border-bottom:1px solid #e9ecef;text-align:right;">{{ Str::limit(strip_tags($presupuesto->concepto_general), 200) }}</td>
  </tr>
  <tr>
    <td style="padding:6px 0;border-bottom:1px solid #e9ecef;"><strong>Fecha emisión</strong></td>
    <td style="padding:6px 0;border-bottom:1px solid #e9ecef;text-align:right;">{{ $presupuesto->fecha_emision?->format('d/m/Y') ?? '—' }}</td>
  </tr>
  @if($presupuesto->fecha_vencimiento)
  <tr>
    <td style="padding:6px 0;border-bottom:1px solid #e9ecef;"><strong>Vigencia hasta</strong></td>
    <td style="padding:6px 0;border-bottom:1px solid #e9ecef;text-align:right;">{{ $presupuesto->fecha_vencimiento->format('d/m/Y') }}</td>
  </tr>
  @endif
  <tr>
    <td style="padding:6px 0;border-bottom:1px solid #e9ecef;"><strong>Subtotal</strong></td>
    <td style="padding:6px 0;border-bottom:1px solid #e9ecef;text-align:right;">${{ $subtotal }} {{ $moneda }}</td>
  </tr>
  @if($conIva)
  <tr>
    <td style="padding:6px 0;border-bottom:1px solid #e9ecef;"><strong>IVA @if($ivaPct)({{ $ivaPct }}%)@endif</strong></td>
    <td style="padding:6px 0;border-bottom:1px solid #e9ecef;text-align:right;">${{ number_format((float) $presupuesto->iva_total, 2) }} {{ $moneda }}</td>
  </tr>
  @endif
  <tr>
    <td style="padding:10px 0 6px;font-size:16px;"><strong>Total</strong></td>
    <td style="padding:10px 0 6px;text-align:right;font-size:18px;font-weight:700;color:#28a745;">${{ $total }} {{ $moneda }}</td>
  </tr>
</table>
