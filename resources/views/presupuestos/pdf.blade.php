<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presupuesto {{ $presupuesto['numero_presupuesto'] ?? 'N/A' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #2c3e50;
            background: #ffffff;
            padding: 0;
            margin: 0;
            line-height: 1.5;
        }

        .budget-container {
            max-width: 210mm;
            margin: 0 auto;
            background: #ffffff;
            padding: 10mm;
        }

        /* Header mejorado */
        .header {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 3px solid #3498db;
            page-break-inside: avoid;
        }

        .header-content {
            width: 100%;
            border-collapse: collapse;
        }

        .logo-section {
            width: 80px;
            vertical-align: top;
        }

        .logo-box {
            width: 60px;
            height: 60px;
            background: #3498db;
            border-radius: 8px;
            text-align: center;
            color: #ffffff;
            font-size: 28px;
            font-weight: bold;
            padding: 16px 0;
        }

        .logo-image {
            width: 60px;
            height: 60px;
            object-fit: contain;
            border-radius: 8px;
        }

        .logo-fallback {
            width: 60px;
            height: 60px;
            background: #3498db;
            border-radius: 8px;
            text-align: center;
            color: #ffffff;
            font-size: 28px;
            font-weight: bold;
            padding: 16px 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .header-info {
            vertical-align: top;
            padding-left: 20px;
        }

        .company-header-name {
            font-size: 16px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 4px;
        }

        .company-header-info {
            font-size: 9px;
            color: #7f8c8d;
            margin-bottom: 3px;
        }

        .folio-section {
            text-align: right;
            vertical-align: top;
        }

        .folio-label {
            font-size: 8px;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .folio-number {
            font-size: 24px;
            font-weight: 700;
            color: #3498db;
            margin-bottom: 4px;
            letter-spacing: -1px;
        }

        .folio-date {
            font-size: 9px;
            color: #7f8c8d;
        }

        /* Sección PARA (cliente) - El proveedor ya está en el header */
        .de-para-section {
            width: 100%;
            margin-bottom: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            page-break-inside: avoid;
        }

        .para-section-full {
            width: 100%;
        }

        .section-label {
            font-size: 9px;
            color: #3498db;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #3498db;
            display: inline-block;
        }

        .company-name {
            font-size: 13px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .company-info {
            font-size: 10px;
            color: #5f6f89;
            margin-bottom: 5px;
            line-height: 1.6;
        }

        .company-info strong {
            color: #34495e;
            font-weight: 600;
        }

        /* Detalles del presupuesto */
        .presupuesto-details {
            width: 100%;
            margin-bottom: 15px;
            padding: 12px;
            background: #ffffff;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            page-break-inside: avoid;
        }

        .presupuesto-details-table {
            width: 100%;
            border-collapse: collapse;
        }

        .detail-item {
            padding: 8px 15px;
            border-right: 1px solid #e9ecef;
        }

        .detail-item:last-child {
            border-right: none;
        }

        .detail-label {
            font-size: 8px;
            color: #7f8c8d;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
            display: block;
            margin-bottom: 5px;
        }

        .detail-value {
            font-size: 11px;
            color: #2c3e50;
            font-weight: 600;
        }

        /* Tabla de conceptos mejorada */
        .servicios-section {
            margin-bottom: 15px;
        }

        .servicios-header-table {
            width: 100%;
            border-collapse: collapse;
            background: #3498db;
            color: #ffffff;
        }

        .servicios-header-table td {
            padding: 12px 10px;
            font-size: 9px;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .servicios-title {
            width: 50%;
        }

        .servicios-cantidad {
            width: 12%;
            text-align: center;
        }

        .servicios-precio {
            width: 19%;
            text-align: right;
        }

        .servicios-importe {
            width: 19%;
            text-align: right;
        }

        .concepto-row {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1px solid #e9ecef;
        }

        .concepto-row:hover {
            background: #f8f9fa;
        }

        .concepto-row td {
            padding: 12px 10px;
            vertical-align: top;
        }

        .concepto-descripcion {
            width: 50%;
            font-size: 10px;
            color: #2c3e50;
            line-height: 1.5;
        }

        .concepto-cantidad {
            width: 12%;
            text-align: center;
            font-size: 10px;
            color: #5f6f89;
            font-weight: 600;
        }

        .concepto-precio {
            width: 19%;
            text-align: right;
            font-size: 10px;
            color: #5f6f89;
        }

        .concepto-importe {
            width: 19%;
            text-align: right;
            font-size: 11px;
            font-weight: 700;
            color: #2c3e50;
        }

        .concepto-badges {
            margin-top: 6px;
        }

        .badge {
            background: #e8f4f8;
            color: #2980b9;
            font-size: 7px;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: 600;
            margin-right: 4px;
            border: 1px solid #bee5eb;
        }

        /* Totales mejorados */
        .totales-section {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            page-break-inside: avoid;
        }

        .total-line-table {
            width: 100%;
            border-collapse: collapse;
        }

        .total-line {
            padding: 8px 0;
        }

        .total-line td {
            padding: 6px 10px;
            font-size: 10px;
            color: #5f6f89;
        }

        .total-line td:first-child {
            text-align: left;
            width: 70%;
        }

        .total-line td:last-child {
            text-align: right;
            font-weight: 600;
            color: #2c3e50;
        }

        .total-line.final-total {
            margin-top: 10px;
            padding-top: 15px;
            border-top: 2px solid #3498db;
        }

        .total-line.final-total td {
            font-size: 13px;
            font-weight: 700;
            color: #2c3e50;
            padding-top: 10px;
        }

        .total-line.final-total td:last-child {
            font-size: 18px;
            color: #3498db;
        }

        .status-badge {
            background: #27ae60;
            color: #ffffff;
            font-size: 7px;
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: 700;
            margin-left: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Footer */
        .footer {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 2px solid #e9ecef;
            text-align: center;
            font-size: 8px;
            color: #7f8c8d;
            page-break-inside: avoid;
        }

        .footer-info {
            margin-bottom: 5px;
        }

        /* Sección de publicidad */
        .publicidad-section {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            page-break-inside: avoid;
        }

        .publicidad-title {
            font-size: 8px;
            color: #7f8c8d;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 1px;
            margin-bottom: 12px;
            text-align: center;
        }

        .publicidad-apps-table {
            width: 100%;
            border-collapse: collapse;
        }

        .publicidad-app {
            text-align: center;
            vertical-align: middle;
            padding: 0 10px;
            width: 33.33%;
        }

        .publicidad-logo-box {
            width: 45px;
            height: 45px;
            margin: 0 auto 6px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: bold;
            color: #ffffff;
        }

        .publicidad-logo-heventec {
            background: #3498db;
        }

        .publicidad-logo-constucc {
            background: #e67e22;
        }

        .publicidad-logo-gestionpro {
            background: #27ae60;
        }

        .publicidad-name {
            font-size: 7px;
            color: #5f6f89;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .notas-section {
            margin-top: 15px;
            padding: 12px;
            background: #fffbf0;
            border-left: 4px solid #f39c12;
            border-radius: 4px;
            page-break-inside: avoid;
        }

        .notas-title {
            font-size: 9px;
            color: #e67e22;
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .notas-content {
            font-size: 9px;
            color: #5f6f89;
            line-height: 1.6;
        }

        @page {
            margin: 10mm 10mm 10mm 10mm;
            size: A4 portrait;
        }

        .no-break {
            page-break-inside: avoid;
        }

        .footer {
            page-break-inside: avoid;
        }

        .totales-section {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    <div class="budget-container">
        <!-- Header mejorado -->
        <div class="header">
            <table class="header-content">
                <tr>
                    <td class="logo-section">
                        @php
                            $logoProveedorBase64 = $presupuesto['logo_proveedor_base64'] ?? '';
                            $nombreEmpresa = $presupuesto['proveedor']->razon_social ?? $presupuesto['proveedor']->nombre_comercial ?? 'P';
                            $inicial = strtoupper(substr($nombreEmpresa, 0, 1));
                        @endphp
                        @if($logoProveedorBase64)
                            <img src="{{ $logoProveedorBase64 }}" alt="Logo" style="width: 60px; height: 60px; object-fit: contain; border-radius: 8px;" />
                        @else
                            <div style="width: 60px; height: 60px; background: #3498db; border-radius: 8px; text-align: center; color: #ffffff; font-size: 28px; font-weight: bold; line-height: 60px;">{{ $inicial }}</div>
                        @endif
                    </td>
                    <td class="header-info">
                        <div class="company-header-name">
                            {{ $presupuesto['proveedor']->razon_social ?? $presupuesto['proveedor']->nombre_comercial ?? 'Empresa Proveedora S.A. de C.V.' }}
                        </div>
                        <div class="company-header-info">
                            @if(isset($presupuesto['proveedor']->rfc))
                                RFC: {{ $presupuesto['proveedor']->rfc }}
                            @endif
                        </div>
                        <div class="company-header-info">
                            {{ $presupuesto['proveedor']->direccion_empresa ?? 'Av. Insurgentes Sur 1234, Col. Del Valle' }}
                        </div>
                        <div class="company-header-info">
                            {{ $presupuesto['proveedor']->ciudad ?? 'Ciudad de México' }}, {{ $presupuesto['proveedor']->estado ?? 'CDMX' }}, México
                        </div>
                        @if(isset($presupuesto['proveedor']->telefono))
                            <div class="company-header-info">
                                Tel: {{ $presupuesto['proveedor']->telefono }}
                            </div>
                        @endif
                        @if(isset($presupuesto['proveedor']->correo))
                            <div class="company-header-info">
                                Email: {{ $presupuesto['proveedor']->correo }}
                            </div>
                        @endif
                    </td>
                    <td class="folio-section">
                        <div class="folio-label">Presupuesto</div>
                        <div class="folio-number">{{ $presupuesto['numero_presupuesto'] ?? 'N/A' }}</div>
                        <div class="folio-date">
                            @if(isset($presupuesto['fecha_emision']))
                                {{ \Carbon\Carbon::parse($presupuesto['fecha_emision'])->format('d/m/Y') }}
                            @elseif(isset($presupuesto->fecha_emision))
                                {{ \Carbon\Carbon::parse($presupuesto->fecha_emision)->format('d/m/Y') }}
                            @else
                                {{ date('d/m/Y') }}
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Información PARA (cliente) - El proveedor ya está en el header -->
        <div class="de-para-section">
            <div class="para-section-full">
                <div class="section-label">Para:</div>
                <div class="company-name">
                    {{ $presupuesto['empresa_receptora']['empresa'] ?? $presupuesto['empresa_receptora']['nombre'] ?? 'Cliente S.A. de C.V.' }}
                </div>
                @if(isset($presupuesto['empresa_receptora']['direccion']))
                    <div class="company-info">
                        <strong>Dirección:</strong> {{ $presupuesto['empresa_receptora']['direccion'] }}
                    </div>
                @endif
                @if(isset($presupuesto['empresa_receptora']['correo']))
                    <div class="company-info">
                        <strong>Email:</strong> {{ $presupuesto['empresa_receptora']['correo'] }}
                    </div>
                @endif
                @if(isset($presupuesto['empresa_receptora']['telefono']))
                    <div class="company-info">
                        <strong>Teléfono:</strong> {{ $presupuesto['empresa_receptora']['telefono'] }}
                    </div>
                @endif
                @if(isset($presupuesto['empresa_receptora']['rfc']))
                    <div class="company-info">
                        <strong>RFC:</strong> {{ $presupuesto['empresa_receptora']['rfc'] }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Detalles del presupuesto -->
        <div class="presupuesto-details">
            <table class="presupuesto-details-table">
                <tr>
                    <td class="detail-item">
                        <span class="detail-label">Fecha de Emisión</span>
                        <span class="detail-value">
                            @if(isset($presupuesto['fecha_emision']))
                                {{ \Carbon\Carbon::parse($presupuesto['fecha_emision'])->format('d/m/Y') }}
                            @elseif(isset($presupuesto->fecha_emision))
                                {{ \Carbon\Carbon::parse($presupuesto->fecha_emision)->format('d/m/Y') }}
                            @else
                                {{ date('d/m/Y') }}
                            @endif
                        </span>
                    </td>
                    <td class="detail-item">
                        <span class="detail-label">Vigencia</span>
                        <span class="detail-value">{{ $presupuesto['condiciones']['vigencia'] ?? '15 Días' }}</span>
                    </td>
                    <td class="detail-item">
                        <span class="detail-label">Moneda</span>
                        <span class="detail-value">MXN (Pesos Mexicanos)</span>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Tabla de conceptos mejorada -->
        <div class="servicios-section">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <td class="servicios-title" style="background: #3498db; color: #ffffff; padding: 12px 10px; font-size: 9px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; width: 50%;">Descripción</td>
                        <td class="servicios-cantidad" style="background: #3498db; color: #ffffff; padding: 12px 10px; font-size: 9px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; width: 12%; text-align: center;">Cantidad</td>
                        <td class="servicios-precio" style="background: #3498db; color: #ffffff; padding: 12px 10px; font-size: 9px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; width: 19%; text-align: right;">Precio Unitario</td>
                        <td class="servicios-importe" style="background: #3498db; color: #ffffff; padding: 12px 10px; font-size: 9px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; width: 19%; text-align: right;">Importe</td>
                    </tr>
                </thead>
                <tbody>
                    @if(isset($presupuesto['conceptos']) && is_array($presupuesto['conceptos']) && count($presupuesto['conceptos']) > 0)
                        @foreach($presupuesto['conceptos'] as $index => $concepto)
                            @php
                                $cantidad = $concepto['cantidad'] ?? 1;
                                $precioUnitario = $concepto['precio_unitario'] ?? 0;
                                $importe = $cantidad * $precioUnitario;
                            @endphp
                            <tr style="border-bottom: 1px solid #e9ecef;">
                                <td class="concepto-descripcion" style="padding: 12px 10px; vertical-align: top; font-size: 10px; color: #2c3e50; line-height: 1.5;">
                                    <strong>{{ $concepto['descripcion'] ?? 'Servicio sin descripción' }}</strong>
                                    @if(($presupuesto['con_iva'] ?? false))
                                        <br><span class="badge" style="background: #e8f4f8; color: #2980b9; font-size: 7px; padding: 2px 6px; border-radius: 3px; font-weight: 600; border: 1px solid #bee5eb;">IVA {{ number_format($presupuesto['iva_porcentaje'] ?? 16, 0) }}%</span>
                                    @endif
                                </td>
                                <td class="concepto-cantidad" style="padding: 12px 10px; text-align: center; font-size: 10px; color: #5f6f89; font-weight: 600;">{{ number_format($cantidad, 0, '.', ',') }}</td>
                                <td class="concepto-precio" style="padding: 12px 10px; text-align: right; font-size: 10px; color: #5f6f89;">${{ number_format($precioUnitario, 2, '.', ',') }}</td>
                                <td class="concepto-importe" style="padding: 12px 10px; text-align: right; font-size: 11px; font-weight: 700; color: #2c3e50;">${{ number_format($importe, 2, '.', ',') }}</td>
                            </tr>
                        @endforeach
                    @elseif(isset($presupuesto->conceptos) && $presupuesto->conceptos->count() > 0)
                        @foreach($presupuesto->conceptos as $concepto)
                            <tr style="border-bottom: 1px solid #e9ecef;">
                                <td class="concepto-descripcion" style="padding: 12px 10px; vertical-align: top; font-size: 10px; color: #2c3e50; line-height: 1.5;">
                                    <strong>{{ $concepto->descripcion ?? 'Servicio sin descripción' }}</strong>
                                    @if(($presupuesto->con_iva ?? false))
                                        <br><span class="badge" style="background: #e8f4f8; color: #2980b9; font-size: 7px; padding: 2px 6px; border-radius: 3px; font-weight: 600; border: 1px solid #bee5eb;">IVA {{ number_format($presupuesto->iva_porcentaje ?? 16, 0) }}%</span>
                                    @endif
                                </td>
                                <td class="concepto-cantidad" style="padding: 12px 10px; text-align: center; font-size: 10px; color: #5f6f89; font-weight: 600;">{{ number_format($concepto->cantidad ?? 1, 0, '.', ',') }}</td>
                                <td class="concepto-precio" style="padding: 12px 10px; text-align: right; font-size: 10px; color: #5f6f89;">${{ number_format($concepto->precio_unitario ?? 0, 2, '.', ',') }}</td>
                                <td class="concepto-importe" style="padding: 12px 10px; text-align: right; font-size: 11px; font-weight: 700; color: #2c3e50;">${{ number_format($concepto->precio_total ?? 0, 2, '.', ',') }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 20px; color: #7f8c8d; font-style: italic;">
                                No hay conceptos registrados
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Totales mejorados -->
        <div class="totales-section">
            @php
                $subtotal = $presupuesto['subtotal'] ?? ($presupuesto->subtotal ?? 0);
                $ivaTotal = $presupuesto['iva_total'] ?? ($presupuesto->iva_total ?? 0);
                $ivaPorcentaje = $presupuesto['iva_porcentaje'] ?? ($presupuesto->iva_porcentaje ?? 16);
                $total = $presupuesto['total'] ?? ($presupuesto->total ?? 0);
                $conIva = $presupuesto['con_iva'] ?? ($presupuesto->con_iva ?? false);
            @endphp

            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 6px 10px; font-size: 10px; color: #5f6f89; width: 70%;">Subtotal</td>
                    <td style="padding: 6px 10px; text-align: right; font-size: 10px; font-weight: 600; color: #2c3e50;">${{ number_format($subtotal, 2, '.', ',') }}</td>
                </tr>
                @if($conIva)
                    <tr>
                        <td style="padding: 6px 10px; font-size: 10px; color: #5f6f89;">IVA ({{ number_format($ivaPorcentaje, 0) }}%)</td>
                        <td style="padding: 6px 10px; text-align: right; font-size: 10px; font-weight: 600; color: #2c3e50;">${{ number_format($ivaTotal, 2, '.', ',') }}</td>
                    </tr>
                @endif
                <tr style="border-top: 2px solid #3498db; margin-top: 10px;">
                    <td style="padding: 10px; font-size: 13px; font-weight: 700; color: #2c3e50;">
                        TOTAL PRESUPUESTO
                        <span style="background: #27ae60; color: #ffffff; font-size: 7px; padding: 3px 8px; border-radius: 3px; font-weight: 700; margin-left: 10px; text-transform: uppercase; letter-spacing: 0.5px;">LISTO</span>
                    </td>
                    <td style="padding: 10px; text-align: right; font-size: 18px; font-weight: 700; color: #3498db;">${{ number_format($total, 2, '.', ',') }}</td>
                </tr>
            </table>
        </div>

        <!-- Notas y condiciones -->
        @if(isset($presupuesto['condiciones']['notas']) || isset($presupuesto['concepto_general']))
            <div class="notas-section">
                <div class="notas-title">Notas y Condiciones</div>
                <div class="notas-content">
                    @if(isset($presupuesto['concepto_general']) && !empty($presupuesto['concepto_general']))
                        {{ $presupuesto['concepto_general'] }}
                    @elseif(isset($presupuesto->concepto_general) && !empty($presupuesto->concepto_general))
                        {{ $presupuesto->concepto_general }}
                    @endif
                    @if(isset($presupuesto['condiciones']['notas']) && !empty($presupuesto['condiciones']['notas']))
                        @if(isset($presupuesto['concepto_general']) && !empty($presupuesto['concepto_general']))
                            <br><br>
                        @endif
                        {{ $presupuesto['condiciones']['notas'] }}
                    @endif
                    @if(isset($presupuesto['condiciones']['vigencia']))
                        <br><br>
                        <strong>Vigencia:</strong> Este presupuesto tiene una vigencia de {{ $presupuesto['condiciones']['vigencia'] }}.
                    @endif
                </div>
            </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <div class="footer-info">
                Este documento es una cotización y no constituye una orden de compra hasta su aceptación formal.
            </div>
            <div class="footer-info">
                Generado el {{ date('d/m/Y') }} a las {{ date('H:i') }} horas
            </div>
        </div>

        <!-- Sección de publicidad -->
        @php
            // Los logos base64 vienen del controlador
            $logosBase64 = $presupuesto['logos_base64'] ?? [];
            $facturaproBase64 = $logosBase64['facturapro'] ?? '';
            $constuccBase64 = $logosBase64['constucc'] ?? '';
            $gestionproBase64 = $logosBase64['gestionpro'] ?? '';
        @endphp
        <div class="publicidad-section" style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e9ecef; page-break-inside: avoid;">
            <div class="publicidad-title" style="font-size: 8px; color: #7f8c8d; text-transform: uppercase; font-weight: 600; letter-spacing: 1px; margin-bottom: 12px; text-align: center;">Desarrollado con nuestras aplicaciones</div>
            <table class="publicidad-apps-table" style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="text-align: center; vertical-align: middle; padding: 0 10px; width: 33.33%;">
                        <table style="margin: 0 auto; border-collapse: collapse;">
                            <tr>
                                <td style="text-align: center;">
                                    @if($facturaproBase64)
                                        <img src="{{ $facturaproBase64 }}" alt="FacturaPro" style="width: 50px; height: 50px; object-fit: contain; margin: 0 auto 6px; display: block;" />
                                    @else
                                        <div style="width: 45px; height: 45px; margin: 0 auto 6px; border-radius: 8px; background: #3498db; color: #ffffff; font-size: 20px; font-weight: bold; line-height: 45px; text-align: center;">H</div>
                                    @endif
                                    <div style="font-size: 7px; color: #5f6f89; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">FacturaPro</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td style="text-align: center; vertical-align: middle; padding: 0 10px; width: 33.33%;">
                        <table style="margin: 0 auto; border-collapse: collapse;">
                            <tr>
                                <td style="text-align: center;">
                                    @if($constuccBase64)
                                        <img src="{{ $constuccBase64 }}" alt="Constucc" style="width: 50px; height: 50px; object-fit: contain; margin: 0 auto 6px; display: block;" />
                                    @else
                                        <div style="width: 45px; height: 45px; margin: 0 auto 6px; border-radius: 8px; background: #e67e22; color: #ffffff; font-size: 20px; font-weight: bold; line-height: 45px; text-align: center;">C</div>
                                    @endif
                                    <div style="font-size: 7px; color: #5f6f89; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Constucc</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td style="text-align: center; vertical-align: middle; padding: 0 10px; width: 33.33%;">
                        <table style="margin: 0 auto; border-collapse: collapse;">
                            <tr>
                                <td style="text-align: center;">
                                    @if($gestionproBase64)
                                        <img src="{{ $gestionproBase64 }}" alt="Gestionpro" style="width: 50px; height: 50px; object-fit: contain; margin: 0 auto 6px; display: block;" />
                                    @else
                                        <div style="width: 45px; height: 45px; margin: 0 auto 6px; border-radius: 8px; background: #27ae60; color: #ffffff; font-size: 20px; font-weight: bold; line-height: 45px; text-align: center;">G</div>
                                    @endif
                                    <div style="font-size: 7px; color: #5f6f89; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Gestionpro</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
