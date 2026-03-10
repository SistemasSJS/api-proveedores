@php
    $margenMm = 18;
    $condicionesDefault = [
        'tiempo_entrega' => '3 días hábiles a partir de anticipo',
        'condiciones_pago' => '50% anticipo, 50% contra entrega',
        'garantia' => '30 días por mano de obra, no incluye mal uso',
        'vigencia' => '7 días naturales',
    ];
    $cond = $presupuesto['condiciones'] ?? [];
    $tiempoEntrega = $cond['tiempo_entrega'] ?? $condicionesDefault['tiempo_entrega'];
    $condicionesPago = $cond['condiciones_pago'] ?? $condicionesDefault['condiciones_pago'];
    $garantia = $cond['garantia'] ?? $condicionesDefault['garantia'];
    $vigencia = $cond['vigencia'] ?? $condicionesDefault['vigencia'];
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Presupuesto {{ $presupuesto['numero_presupuesto'] ?? 'N/A' }}</title>
    <style>
        @page {
            size: letter;
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 8.5pt;
            color: #2c3e50;
            background: #ffffff;
            line-height: 1.15;
            margin: 0;
            padding: 0;
        }

        /* Elementos de margen (cuando @page margin no funciona) */
        .margin-top {
            height: {{ $margenMm }}mm;
            min-height: {{ $margenMm }}mm;
            clear: both;
        }

        .margin-bottom {
            height: {{ $margenMm }}mm;
            min-height: {{ $margenMm }}mm;
            clear: both;
        }

        .margin-sides {
            padding-left: {{ $margenMm }}mm;
            padding-right: {{ $margenMm }}mm;
        }

        .document-container {
            width: 100%;
            max-width: 100%;
            margin: 0;
            padding: 0;
            background: #ffffff;
            overflow-x: hidden;
        }

        /* ========== 1) ENCABEZADO (igual que preview) ========== */
        .header {
            margin-bottom: 4mm;
            padding-bottom: 3mm;
            border-bottom: 2px solid #3498db;
            page-break-inside: avoid;
        }

        .header-content {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .logo-section {
            width: 14%;
            min-width: 0;
            vertical-align: top;
            padding-right: 2mm;
        }

        .header-info {
            vertical-align: top;
            padding-left: 4mm;
            width: 55%;
            min-width: 0;
            overflow: hidden;
            word-wrap: break-word;
        }

        .folio-section {
            width: 31%;
            min-width: 0;
            vertical-align: top;
            text-align: right;
            padding-left: 2mm;
            overflow: hidden;
        }

        .logo-img {
            max-width: 100%;
            max-height: 16mm;
            object-fit: contain;
            border-radius: 2mm;
        }

        .logo-fallback {
            width: 14mm;
            height: 14mm;
            background: #3498db;
            border-radius: 2mm;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 11pt;
            font-weight: bold;
        }

        .company-header-name {
            font-size: 9pt;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.8mm;
            line-height: 1.15;
        }

        .company-header-info {
            font-size: 6.5pt;
            color: #7f8c8d;
            margin-bottom: 0.6mm;
            line-height: 1.15;
        }

        .folio-label {
            font-size: 6pt;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            margin-bottom: 0.8mm;
            line-height: 1.1;
        }

        .folio-number {
            font-size: 14pt;
            font-weight: 700;
            color: #3498db;
            margin-bottom: 0.8mm;
            letter-spacing: -0.5pt;
            word-wrap: break-word;
            line-height: 1.1;
            overflow: hidden;
        }

        .folio-date {
            font-size: 7pt;
            color: #7f8c8d;
            line-height: 1.15;
        }

        /* ========== 2) DATOS DEL RECEPTOR (igual que preview) ========== */
        .receptor-section {
            width: 100%;
            margin-bottom: 4mm;
            padding: 3mm;
            background: #f8f9fa;
            border-radius: 2mm;
            border: 1px solid #e9ecef;
            page-break-inside: avoid;
        }

        .receptor-title {
            font-size: 7pt;
            color: #3498db;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 2mm;
            padding-bottom: 1mm;
            border-bottom: 2px solid #3498db;
            display: inline-block;
            line-height: 1.1;
        }

        .receptor-name {
            font-size: 9pt;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 1mm;
            line-height: 1.15;
        }

        .receptor-info {
            font-size: 7pt;
            color: #5f6f89;
            margin-bottom: 0.8mm;
            line-height: 1.15;
        }

        .receptor-info strong {
            color: #34495e;
            font-weight: 600;
        }

        /* ========== 3) DESCRIPCIÓN GENERAL ========== */
        .descripcion-section {
            margin-bottom: 4mm;
            page-break-inside: avoid;
        }

        .descripcion-title {
            font-size: 8pt;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 1mm;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
            line-height: 1.1;
        }

        .descripcion-text {
            font-size: 7pt;
            color: #34495e;
            text-align: justify;
            line-height: 1.25;
        }

        /* ========== 4) TÍTULO Y TABLA PRESUPUESTO ========== */
        .presupuesto-title {
            text-align: center;
            font-size: 12pt;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 3mm;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
            line-height: 1.1;
        }

        .presupuesto-table {
            width: 100%;
            max-width: 100%;
            border-collapse: collapse;
            margin-bottom: 4mm;
            page-break-inside: avoid;
            table-layout: fixed;
            overflow: hidden;
        }

        .presupuesto-table thead {
            background: #3498db;
            color: #ffffff;
        }

        .presupuesto-table thead td {
            padding: 1.5mm 1mm;
            font-size: 6pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: center;
            border: 1px solid #2980b9;
            line-height: 1.1;
        }

        .presupuesto-table thead td:first-child { width: 5%; }
        .presupuesto-table thead td:nth-child(2) {
            width: 38%;
            text-align: left;
            padding-left: 1.5mm;
        }
        .presupuesto-table thead td:nth-child(3) { width: 10%; }
        .presupuesto-table thead td:nth-child(4) { width: 10%; }
        .presupuesto-table thead td:nth-child(5),
        .presupuesto-table thead td:nth-child(6) {
            width: 18%;
            text-align: right;
            padding-right: 1mm;
        }

        .presupuesto-table tbody td {
            padding: 1.2mm 1mm;
            font-size: 6.5pt;
            border: 1px solid #e9ecef;
            vertical-align: top;
            line-height: 1.15;
            overflow: hidden;
        }

        .presupuesto-table tbody td:first-child {
            text-align: center;
            font-weight: 600;
            color: #7f8c8d;
        }

        .presupuesto-table tbody td:nth-child(2) {
            text-align: left;
            color: #2c3e50;
            padding-left: 1.5mm;
            word-wrap: break-word;
        }

        .presupuesto-table tbody td:nth-child(3),
        .presupuesto-table tbody td:nth-child(4) {
            text-align: center;
            color: #5f6f89;
            text-transform: uppercase;
        }

        .presupuesto-table tbody td:nth-child(5),
        .presupuesto-table tbody td:nth-child(6) {
            text-align: right;
            color: #2c3e50;
            padding-right: 1mm;
        }

        .presupuesto-table tbody td:nth-child(6) {
            font-weight: 600;
        }

        .presupuesto-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        .no-conceptos {
            padding: 6mm;
            text-align: center;
            color: #95a5a6;
            font-style: italic;
            font-size: 7pt;
        }

        /* ========== 5) TOTALES (alineado con tabla) ========== */
        .totales-section {
            margin-bottom: 4mm;
            page-break-inside: avoid;
        }

        .totales-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .totales-table td {
            padding: 1mm 1mm 1.5mm 1mm;
            font-size: 7pt;
            vertical-align: middle;
        }

        .totales-table td:first-child {
            width: 82%;
            text-align: right;
            color: #5f6f89;
            padding-right: 2mm;
        }

        .totales-table td:last-child {
            width: 18%;
            text-align: right;
            color: #2c3e50;
            font-weight: 600;
            padding-right: 0;
        }

        .totales-table .total-line-final td {
            padding-top: 2mm;
            border-top: 2px solid #3498db;
        }

        .totales-table .total-line-final td:first-child {
            font-size: 9pt;
            font-weight: 700;
            color: #2c3e50;
        }

        .totales-table .total-line-final td:last-child {
            font-size: 10pt;
            font-weight: 700;
            color: #3498db;
        }

        /* ========== 6) CONDICIONES (igual que preview, siempre visibles) ========== */
        .condiciones-section {
            margin-bottom: 4mm;
            page-break-inside: avoid;
        }

        .condiciones-title {
            font-size: 8pt;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 2mm;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
            line-height: 1.1;
        }

        .condiciones-list {
            list-style: none;
            padding: 0;
        }

        .condiciones-list li {
            font-size: 6.5pt;
            color: #34495e;
            margin-bottom: 0.8mm;
            line-height: 1.15;
            padding-left: 4mm;
            position: relative;
        }

        .condiciones-list li::before {
            content: "•";
            position: absolute;
            left: 0;
            color: #3498db;
            font-weight: bold;
        }

        .condiciones-list li strong {
            color: #2c3e50;
            font-weight: 600;
        }

        /* ========== 7) OBSERVACIONES ========== */
        .observaciones-section {
            margin-bottom: 4mm;
            page-break-inside: avoid;
        }

        .observaciones-title {
            font-size: 8pt;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 1mm;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
            line-height: 1.1;
        }

        .observaciones-text {
            font-size: 6.5pt;
            color: #34495e;
            text-align: justify;
            line-height: 1.2;
        }

        /* ========== 8) PIE DE PÁGINA ========== */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 18mm;
            padding-top: 2mm;
            padding-left: {{ $margenMm }}mm;
            padding-right: {{ $margenMm }}mm;
            border-top: 1px solid #e9ecef;
            background: #ffffff;
            font-size: 6pt;
            color: #7f8c8d;
            line-height: 1.2;
        }

        .footer-content {
            width: 100%;
            text-align: center;
        }

        .footer-pages {
            margin-bottom: 0.5mm;
        }

        .footer-qr {
            display: inline-block;
            width: 12mm;
            height: 12mm;
            margin-top: 1mm;
        }

        .footer-qr img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
    </style>
</head>
<body>
    <div class="margin-top"></div>
    <div class="margin-sides">
        <div class="document-container">
        <!-- 1) ENCABEZADO -->
        <div class="header">
            <table class="header-content">
                <tr>
                    <td class="logo-section">
                        @php
                            $logoProveedorBase64 = ($presupuesto['condiciones']['emisor_logo'] ?? null) ?: ($presupuesto['logo_proveedor_base64'] ?? null);
                            $nombreEmpresa = $presupuesto['proveedor']->razon_social ?? $presupuesto['proveedor']->nombre_comercial ?? 'P';
                            $inicial = strtoupper(substr($nombreEmpresa, 0, 1));
                        @endphp
                        @if($logoProveedorBase64)
                            <img src="{{ $logoProveedorBase64 }}" alt="Logo" class="logo-img" />
                        @else
                            <div class="logo-fallback">{{ $inicial }}</div>
                        @endif
                    </td>
                    <td class="header-info">
                        @php
                            $emisorNombre = $presupuesto['condiciones']['emisor_razon_social'] ?? $presupuesto['proveedor']->razon_social ?? $presupuesto['proveedor']->nombre_comercial ?? 'Empresa Proveedora S.A. de C.V.';
                            $emisorRfc = $presupuesto['condiciones']['emisor_rfc'] ?? $presupuesto['proveedor']->rfc;
                            $emisorDireccion = $presupuesto['condiciones']['emisor_direccion'] ?? $presupuesto['proveedor']->direccion_empresa;
                            $emisorCiudad = $presupuesto['condiciones']['emisor_ciudad_estado'] ?? null;
                            if (!$emisorCiudad) {
                                $df = $presupuesto['proveedor']->direccion_fiscal ?? null;
                                $ciudad = $presupuesto['proveedor']->ciudad ?? ($df ? ($df->ciudad ?? 'Ciudad de México') : 'Ciudad de México');
                                $estado = $df ? ($df->estado ?? 'CDMX') : 'CDMX';
                                $emisorCiudad = $ciudad . ', ' . $estado . ', México';
                            }
                            $emisorTel = $presupuesto['condiciones']['emisor_telefono'] ?? $presupuesto['proveedor']->telefono;
                            $emisorEmail = $presupuesto['condiciones']['emisor_email'] ?? $presupuesto['proveedor']->email;
                        @endphp
                        <div class="company-header-name">{{ $emisorNombre }}</div>
                        @if($emisorRfc)
                            <div class="company-header-info">RFC: {{ $emisorRfc }}</div>
                        @endif
                        @if($emisorDireccion)
                            <div class="company-header-info">{{ $emisorDireccion }}</div>
                        @endif
                        @if($emisorCiudad)
                            <div class="company-header-info">{{ $emisorCiudad }}</div>
                        @endif
                        @if($emisorTel)
                            <div class="company-header-info">Tel: {{ $emisorTel }}</div>
                        @endif
                        @if($emisorEmail)
                            <div class="company-header-info">Email: {{ $emisorEmail }}</div>
                        @endif
                    </td>
                    <td class="folio-section">
                        <div class="folio-label">Presupuesto</div>
                        <div class="folio-number">{{ $presupuesto['numero_presupuesto'] ?? 'PRES-000001' }}</div>
                        <div class="folio-date">
                            @php
                                $fecha = $presupuesto['fecha_emision'] ?? now();
                                if (is_string($fecha)) {
                                    $fecha = \Carbon\Carbon::parse($fecha);
                                }
                                $dia = str_pad($fecha->day, 2, '0', STR_PAD_LEFT);
                                $mes = str_pad($fecha->month, 2, '0', STR_PAD_LEFT);
                                $anio = $fecha->year;
                            @endphp
                            {{ $dia }}/{{ $mes }}/{{ $anio }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- 2) DATOS DEL RECEPTOR -->
        <div class="receptor-section">
            <div class="receptor-title">Datos del receptor</div>

            @php
                $empresa = $presupuesto['empresa_receptora']['empresa'] ?? null;
                $nombre = $presupuesto['empresa_receptora']['nombre'] ?? null;
            @endphp
            @if($empresa)
                <div class="receptor-name">{{ $empresa }}</div>
            @elseif($nombre)
                <div class="receptor-name">{{ $nombre }}</div>
            @endif

            @if($nombre)
                <div class="receptor-info"><strong>Nombre:</strong> {{ $nombre }}</div>
            @endif

            @if($presupuesto['empresa_receptora']['puesto'] ?? null)
                <div class="receptor-info"><strong>Cargo o puesto:</strong> {{ $presupuesto['empresa_receptora']['puesto'] }}</div>
            @endif

            @if($presupuesto['empresa_receptora']['correo'] ?? null)
                <div class="receptor-info"><strong>Email:</strong> {{ $presupuesto['empresa_receptora']['correo'] }}</div>
            @endif

            @if($presupuesto['empresa_receptora']['telefono'] ?? null)
                <div class="receptor-info"><strong>Teléfono:</strong> {{ $presupuesto['empresa_receptora']['telefono'] }}</div>
            @endif

            @if($presupuesto['empresa_receptora']['direccion'] ?? null)
                <div class="receptor-info"><strong>Dirección:</strong> {{ $presupuesto['empresa_receptora']['direccion'] }}</div>
            @endif
        </div>

        <!-- 3) DESCRIPCIÓN GENERAL -->
        @if($presupuesto['concepto_general'] ?? null)
            <div class="descripcion-section">
                <div class="descripcion-title">Descripción general</div>
                <div class="descripcion-text">{{ $presupuesto['concepto_general'] }}</div>
            </div>
        @endif

        <!-- 4) TÍTULO Y TABLA PRESUPUESTO -->
        <div class="presupuesto-title">Presupuesto</div>

        <table class="presupuesto-table">
            <thead>
                <tr>
                    <td>#</td>
                    <td>Descripción</td>
                    <td>Cantidad</td>
                    <td>Unidad</td>
                    <td>Precio Unitario</td>
                    <td>Importe</td>
                </tr>
            </thead>
            <tbody>
                @php
                    $conceptos = $presupuesto['conceptos'] ?? [];
                    $subtotal = 0;
                @endphp
                @if(count($conceptos) > 0)
                    @foreach($conceptos as $index => $concepto)
                        @php
                            $cantidad = $concepto['cantidad'] ?? 1;
                            $precioUnitario = $concepto['precio_unitario'] ?? 0;
                            $importe = $cantidad * $precioUnitario;
                            $subtotal += $importe;
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $concepto['descripcion'] ?? 'Sin descripción' }}</td>
                            <td>{{ number_format($cantidad, 2, '.', ',') }}</td>
                            <td>{{ strtoupper($concepto['unidad'] ?? 'PZA') }}</td>
                            <td>${{ number_format($precioUnitario, 2, '.', ',') }}</td>
                            <td>${{ number_format($importe, 2, '.', ',') }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="6" class="no-conceptos">No hay conceptos registrados</td>
                    </tr>
                @endif
            </tbody>
        </table>

        <!-- 5) TOTALES -->
        <div class="totales-section">
            @php
                $subtotalCalculado = $presupuesto['subtotal'] ?? $subtotal;
                $conIva = $presupuesto['con_iva'] ?? false;
                $ivaPorcentaje = $presupuesto['iva_porcentaje'] ?? 16;
                $ivaTotal = $conIva ? ($subtotalCalculado * ($ivaPorcentaje / 100)) : 0;
                $total = $subtotalCalculado + $ivaTotal;
            @endphp
            <table class="totales-table">
                <tr>
                    <td>Subtotal:</td>
                    <td>${{ number_format($subtotalCalculado, 2, '.', ',') }}</td>
                </tr>
                @if($conIva)
                    <tr>
                        <td>IVA ({{ number_format($ivaPorcentaje, 0) }}%):</td>
                        <td>${{ number_format($ivaTotal, 2, '.', ',') }}</td>
                    </tr>
                @endif
                <tr class="total-line-final">
                    <td>TOTAL:</td>
                    <td>${{ number_format($total, 2, '.', ',') }}</td>
                </tr>
            </table>
        </div>

        <!-- 6) CONDICIONES (siempre visibles con defaults) -->
        <div class="condiciones-section">
            <div class="condiciones-title">Condiciones</div>
            <ul class="condiciones-list">
                <li><strong>Tiempo de entrega:</strong> {{ $tiempoEntrega }}</li>
                <li><strong>Condiciones de pago:</strong> {{ $condicionesPago }}</li>
                <li><strong>Garantía:</strong> {{ $garantia }}</li>
                <li><strong>Vigencia del presupuesto:</strong> {{ $vigencia }}</li>
                <li><strong>Moneda:</strong> MXN (Pesos Mexicanos)</li>
                @if($presupuesto['proveedor']->datos_bancarios ?? null)
                    <li>
                        <strong>Datos bancarios:</strong>
                        @php
                            $bancarios = is_string($presupuesto['proveedor']->datos_bancarios)
                                ? json_decode($presupuesto['proveedor']->datos_bancarios, true)
                                : $presupuesto['proveedor']->datos_bancarios;
                        @endphp
                        @if($bancarios)
                            Banco: {{ $bancarios['banco'] ?? 'N/A' }},
                            CLABE: {{ $bancarios['clabe_interbancaria'] ?? 'N/A' }},
                            Cuenta: {{ $bancarios['numero_cuenta'] ?? 'N/A' }}
                        @endif
                    </li>
                @endif
            </ul>
        </div>

        <!-- 7) OBSERVACIONES -->
        @if($presupuesto['observaciones'] ?? null)
            <div class="observaciones-section">
                <div class="observaciones-title">Observaciones</div>
                <div class="observaciones-text">{{ $presupuesto['observaciones'] }}</div>
            </div>
        @endif
        </div>
    </div>
    <div class="margin-bottom"></div>

    <!-- 8) PIE DE PÁGINA -->
    <div class="footer">
        <div class="footer-content">
            <div class="footer-pages">
                Página <span class="page-number"></span> de <span class="total-pages"></span>
            </div>
            @if(isset($presupuesto['qr_code']))
                <div class="footer-qr">
                    <img src="{{ $presupuesto['qr_code'] }}" alt="QR Presupuesto" />
                </div>
            @endif
        </div>
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $text = "Página {PAGE_NUM} de {PAGE_COUNT}";
            $size = 7;
            $font = $fontMetrics->getFont("DejaVu Sans");
            $width = $fontMetrics->get_text_width($text, $font, $size) / 2;
            $x = ($pdf->get_width() - $width) / 2;
            $y = $pdf->get_height() - 35;
            $pdf->page_text($x, $y, $text, $font, $size);
        }
    </script>
</body>
</html>
