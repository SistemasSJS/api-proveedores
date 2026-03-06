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
            font-size: 12px;
            color: #1f2c3f;
            background: #ffffff;
            padding: 20px;
        }

        .budget-container {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e8edf5;
        }

        .logo-section {
            width: 48px;
            height: 48px;
            background: #1a1a1a;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 20px;
            font-weight: bold;
        }

        .folio-section {
            text-align: right;
        }

        .folio-number {
            font-size: 24px;
            font-weight: 700;
            color: #1f2c3f;
            margin-bottom: 4px;
        }

        .folio-label {
            font-size: 9px;
            color: #7b8ca8;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }

        .de-para-section {
            width: 100%;
            margin-bottom: 24px;
            padding-bottom: 24px;
            border-bottom: 1px solid #e8edf5;
        }

        .de-para-table {
            width: 100%;
            border-collapse: collapse;
        }

        .de-section, .para-section {
            width: 50%;
            vertical-align: top;
            padding-right: 20px;
        }

        .para-section {
            padding-right: 0;
            padding-left: 20px;
        }

        .section-label {
            font-size: 8px;
            color: #7b8ca8;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .company-name {
            font-size: 11px;
            font-weight: 600;
            color: #1f2c3f;
            margin-bottom: 6px;
            line-height: 1.4;
        }

        .company-info {
            font-size: 10px;
            color: #5f6f89;
            margin-bottom: 4px;
            line-height: 1.5;
        }

        .presupuesto-details {
            width: 100%;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e8edf5;
        }

        .presupuesto-details-table {
            width: 100%;
            border-collapse: collapse;
        }

        .detail-item {
            padding-right: 24px;
        }

        .detail-label {
            font-size: 8px;
            color: #7b8ca8;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
            display: block;
            margin-bottom: 4px;
        }

        .detail-value {
            font-size: 10px;
            color: #1f2c3f;
            font-weight: 600;
        }

        .servicios-section {
            margin-bottom: 24px;
        }

        .servicios-header {
            width: 100%;
            padding-bottom: 12px;
            margin-bottom: 16px;
            border-bottom: 2px solid #e8edf5;
        }

        .servicios-header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .servicios-title, .servicios-importe {
            font-size: 8px;
            color: #7b8ca8;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .servicios-importe {
            text-align: right;
        }

        .servicio-item {
            width: 100%;
            padding: 16px 0;
            border-bottom: 1px dashed #e8edf5;
        }

        .servicio-item-table {
            width: 100%;
            border-collapse: collapse;
        }

        .servicio-content {
            width: 70%;
        }

        .servicio-title {
            font-size: 11px;
            font-weight: 600;
            color: #1f2c3f;
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .servicio-badges {
            margin-top: 6px;
        }

        .badge {
            display: inline-block;
            background: #f0f3f8;
            color: #5f6f89;
            font-size: 8px;
            padding: 3px 8px;
            border-radius: 10px;
            font-weight: 600;
            margin-right: 6px;
        }

        .servicio-amount {
            width: 30%;
            text-align: right;
            font-size: 11px;
            font-weight: 700;
            color: #1f2c3f;
            vertical-align: top;
            padding-left: 16px;
        }

        .totales-section {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 2px solid #e8edf5;
        }

        .total-line {
            width: 100%;
            padding: 8px 0;
            font-size: 10px;
            color: #5f6f89;
        }

        .total-line-table {
            width: 100%;
            border-collapse: collapse;
        }

        .total-line span {
            width: 70%;
        }

        .total-line strong {
            text-align: right;
            color: #1f2c3f;
            font-weight: 600;
        }

        .total-line.final-total {
            margin-top: 12px;
            padding-top: 16px;
            border-top: 2px solid #dbe4f2;
            font-size: 12px;
            font-weight: 700;
            color: #1f2c3f;
        }

        .total-line.final-total strong {
            font-size: 16px;
            color: #2f6bff;
        }

        .status-badge {
            display: inline-block;
            background: #d4edda;
            color: #155724;
            font-size: 8px;
            padding: 4px 10px;
            border-radius: 10px;
            font-weight: 700;
            margin-left: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        @page {
            margin: 20mm;
        }
    </style>
</head>
<body>
    <div class="budget-container">
        <!-- Header con logo y folio -->
        <div class="header">
            <div class="logo-section">P</div>
            <div class="folio-section">
                <div class="folio-number">{{ $presupuesto['numero_presupuesto'] ?? 'N/A' }}</div>
                <div class="folio-label">ORIGINAL BUDGET</div>
            </div>
        </div>

        <!-- Información DE/PARA -->
        <div class="de-para-section">
            <table class="de-para-table">
                <tr>
                    <td class="de-section">
                        <div class="section-label">DE:</div>
                        <div class="company-name">{{ $presupuesto['proveedor']->razon_social ?? $presupuesto['proveedor']->nombre_comercial ?? 'Empresa Proveedora S.A. de C.V.' }}</div>
                        <div class="company-info">{{ $presupuesto['proveedor']->direccion_empresa ?? 'Av. Insurgentes Sur 1234, Col. Del Valle' }}</div>
                        <div class="company-info">{{ $presupuesto['proveedor']->ciudad ?? 'Ciudad de México' }}, MX</div>
                    </td>
                    <td class="para-section">
                        <div class="section-label">PARA:</div>
                        <div class="company-name">{{ $presupuesto['empresa_receptora']['empresa'] ?? $presupuesto['empresa_receptora']['nombre'] ?? 'Cliente S.A. de C.V.' }}</div>
                        <div class="company-info">{{ $presupuesto['empresa_receptora']['direccion'] ?? 'Av. Reforma 567, Col. Juárez' }}</div>
                        <div class="company-info">{{ $presupuesto['empresa_receptora']['correo'] ?? 'contacto@cliente.com' }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Detalles del presupuesto -->
        <div class="presupuesto-details">
            <table class="presupuesto-details-table">
                <tr>
                    <td class="detail-item">
                        <span class="detail-label">EMISIÓN:</span>
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
                        <span class="detail-label">VALIDEZ:</span>
                        <span class="detail-value">{{ $presupuesto['condiciones']['vigencia'] ?? '15 Días' }}</span>
                    </td>
                    <td class="detail-item">
                        <span class="detail-label">MONEDA:</span>
                        <span class="detail-value">MXN ($)</span>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Descripción del servicio -->
        <div class="servicios-section">
            <table class="servicios-header-table">
                <tr>
                    <td class="servicios-title">DESCRIPCIÓN DEL SERVICIO</td>
                    <td class="servicios-importe">IMPORTE</td>
                </tr>
            </table>

            @if(isset($presupuesto['conceptos']) && is_array($presupuesto['conceptos']))
                @foreach($presupuesto['conceptos'] as $concepto)
                    @php
                        $importe = ($concepto['cantidad'] ?? 0) * ($concepto['precio_unitario'] ?? 0);
                    @endphp
                    <table class="servicio-item-table servicio-item">
                        <tr>
                            <td class="servicio-content">
                                <div class="servicio-title">{{ $concepto['descripcion'] ?? 'Servicio sin descripción' }}</div>
                                <div class="servicio-badges">
                                    <span class="badge">CANT:{{ $concepto['cantidad'] ?? 1 }}</span>
                                    @if(($presupuesto['con_iva'] ?? false))
                                        <span class="badge">IVA: {{ number_format($presupuesto['iva_porcentaje'] ?? 16, 0) }}%</span>
                                    @endif
                                </div>
                            </td>
                            <td class="servicio-amount">${{ number_format($importe, 2, '.', ',') }}</td>
                        </tr>
                    </table>
                @endforeach
            @elseif(isset($presupuesto->conceptos))
                @foreach($presupuesto->conceptos as $concepto)
                    <table class="servicio-item-table servicio-item">
                        <tr>
                            <td class="servicio-content">
                                <div class="servicio-title">{{ $concepto->descripcion ?? 'Servicio sin descripción' }}</div>
                                <div class="servicio-badges">
                                    <span class="badge">CANT:{{ $concepto->cantidad ?? 1 }}</span>
                                    @if(($presupuesto->con_iva ?? false))
                                        <span class="badge">IVA: {{ number_format($presupuesto->iva_porcentaje ?? 16, 0) }}%</span>
                                    @endif
                                </div>
                            </td>
                            <td class="servicio-amount">${{ number_format($concepto->precio_total ?? 0, 2, '.', ',') }}</td>
                        </tr>
                    </table>
                @endforeach
            @endif
        </div>

        <!-- Totales -->
        <div class="totales-section">
            @php
                $subtotal = $presupuesto['subtotal'] ?? ($presupuesto->subtotal ?? 0);
                $ivaTotal = $presupuesto['iva_total'] ?? ($presupuesto->iva_total ?? 0);
                $ivaPorcentaje = $presupuesto['iva_porcentaje'] ?? ($presupuesto->iva_porcentaje ?? 16);
                $total = $presupuesto['total'] ?? ($presupuesto->total ?? 0);
                $conIva = $presupuesto['con_iva'] ?? ($presupuesto->con_iva ?? false);
            @endphp

            <table class="total-line-table total-line">
                <tr>
                    <td><span>Subtotal base</span></td>
                    <td><strong>${{ number_format($subtotal, 2, '.', ',') }}</strong></td>
                </tr>
            </table>
            @if($conIva)
                <table class="total-line-table total-line">
                    <tr>
                        <td><span>IVA ({{ number_format($ivaPorcentaje, 0) }}%)</span></td>
                        <td><strong>${{ number_format($ivaTotal, 2, '.', ',') }}</strong></td>
                    </tr>
                </table>
            @endif
            <table class="total-line-table total-line final-total">
                <tr>
                    <td><span>TOTAL PRESUPUESTO <span class="status-badge">LISTO</span></span></td>
                    <td><strong>${{ number_format($total, 2, '.', ',') }}</strong></td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
