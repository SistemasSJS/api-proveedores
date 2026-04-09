@php
    $margenMm = 20;
    $footerHeightMm = 25.4;
    $terminosLista = $presupuesto['terminos_enunciados'] ?? [];
    $observacionesLista = $presupuesto['observaciones_enunciados'] ?? [];
@endphp
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Presupuesto {{ $presupuesto['numero_presupuesto'] ?? 'N/A' }}</title>
    {{--
      Plantilla PDF alternativa (utilidades estilo Tailwind embebidas).
      Acento corporativo azul #3498db (alineado con pdf.blade.php). DomPDF: sin CDN.
    --}}
    <style>
        @page {
            size: letter;
            margin: 25.5mm;
        }

        :root {
            --tw-slate-50: #f8fafc;
            --tw-slate-100: #f1f5f9;
            --tw-slate-200: #e2e8f0;
            --tw-slate-400: #94a3b8;
            --tw-slate-500: #64748b;
            --tw-slate-600: #475569;
            --tw-slate-700: #334155;
            --tw-slate-800: #1e293b;
            --tw-slate-900: #0f172a;
            --accent: #3498db;
            --accent-dark: #2980b9;
            --accent-soft: #eaf4fc;
            --accent-border: #d6eaf8;
            --heading: #2c3e50;
            --tw-white: #ffffff;
            /* Interlineado unificado en bloques de texto del cuerpo */
            --section-line-height: 1.05;
        }

        html,
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 8.5pt;
            color: var(--tw-slate-800);
            background: var(--tw-white);
            line-height: 1.2;
            margin: 0;
            padding: 0;
            padding-bottom: {{ $footerHeightMm }}mm;
        }

        body {
            padding-top: {{ $margenMm }}mm;
        }

        .margin-sides {
            padding-left: {{ $margenMm }}mm;
            padding-right: {{ $margenMm }}mm;
        }

        .document-container {
            width: 100%;
            background: var(--tw-white);
        }

        /* —— Encabezado (rejilla 3 columnas, sin barra lateral) —— */
        .tw-header-wrap {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
            page-break-inside: avoid;
        }

        .tw-header-main {
            vertical-align: top;
            padding: 0;
            background: transparent;
        }

        /* Línea gris completa bajo el encabezado (como referencia) */
        .tw-header-rule {
            width: 100%;
            height: 0;
            margin: 3mm 0 4mm 0;
            padding: 0;
            border: 0;
            border-top: 1px solid var(--tw-slate-200);
            font-size: 0;
            line-height: 0;
            overflow: hidden;
        }

        .tw-header-top {
            width: 100%;
            border-collapse: collapse;
        }

        .tw-logo-cell {
            width: 22%;
            vertical-align: top;
            text-align: left;
            padding-right: 0.7mm; /* 👈 aquí está la magia */
        }

        .tw-logo-img {
            max-width: 40mm;   /* 4 cm */
            max-height: 30mm;  /* 3 cm */
            width: auto;
            height: auto;
            object-fit: contain;
            display: block;
        }

        .tw-logo-fallback {
            width: 20mm;
            height: 20mm;
            background: var(--accent);
            border-radius: 1mm;
            text-align: center;
            line-height: 20mm;
            color: var(--tw-white);
            font-size: 11pt;
            font-weight: bold;
        }

        .tw-emisor-cell {
            vertical-align: top;
            padding-left: 0;
            width: 48%;
        }

        .tw-emisor-name {
            font-size: 9.5pt;
            font-weight: 700;
            color: var(--heading);
            text-transform: uppercase;
            margin-bottom: 0.4mm;
            letter-spacing: 0.03em;
            line-height: var(--section-line-height);
        }

        .tw-emisor-line {
            font-size: 7pt;
            color: var(--tw-slate-600);
            margin-bottom: 0.2mm;
            line-height: var(--section-line-height);
        }

        .tw-folio-cell {
            vertical-align: top;
            text-align: right;
            width: 30%;
        }

        .tw-badge-label {
            font-size: 6pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--tw-slate-500);
            margin-bottom: 0.6mm;
        }

        .tw-badge-folio {
            display: block;
            background: transparent;
            border: none;
            color: var(--accent);
            font-size: 13pt;
            font-weight: 700;
            padding: 0;
            margin: 0 0 1mm 0;
            line-height: 1.1;
        }

        .tw-uuid {
            font-size: 6pt;
            color: var(--tw-slate-500);
            word-break: break-all;
            max-width: 100%;
        }

        .tw-date {
            font-size: 7pt;
            color: var(--tw-slate-800);
            margin-top: 0.6mm;
        }

        /* Cajas grises (referencia): fondo claro, borde suave, sin acento lateral */
        .tw-card {
            margin-bottom: 3mm;
            padding: 1mm 2mm;
            /* background: var(--tw-slate-100); */
            /* border: 1px solid var(--tw-slate-200); */
            border-radius: 1mm;
            page-break-inside: avoid;
        }

        .tw-card-title {
            font-size: 6.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--accent);
            margin: 0 0 1mm 0;
            padding: 0;
            border: none;
        }

        .tw-receptor-strong {
            font-size: 9pt;
            font-weight: 700;
            color: var(--heading);
            margin-bottom: 0.3mm;
            line-height: var(--section-line-height);
        }

        .tw-receptor-line {
            font-size: 7pt;
            color: var(--tw-slate-600);
            margin-bottom: 0.2mm;
            line-height: var(--section-line-height);
        }

        /* Descripción */
        .tw-desc-box {
            margin-bottom: 3mm;
            padding: 1mm 2mm;
            /* background: var(--tw-slate-100); */
            /* border: 1px solid var(--tw-slate-200); */
            border-radius: 1mm;
            page-break-inside: avoid;
        }

        .tw-desc-title {
            font-size: 9pt;       /* antes 6.5pt */
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--accent);
            margin: 0 0 1.5mm 0;  /* un poco más de espacio */
        }

        .tw-desc-text {
            font-size: 9pt;       /* antes 7pt */
            font-weight: 600;     /* 🔥 esto es clave */
            color: var(--tw-slate-900);
            text-align: justify;
            line-height: 1.3;     /* más aire */
        }

        /* Título bloque tabla (referencia: centrado, negro, sin subrayado azul) */
        .tw-section-title {
            font-size: 9.5pt;
            font-weight: 700;
            color: var(--heading);
            text-transform: none;
            letter-spacing: 0.02em;
            text-align: center;
            margin: 4mm 0 2mm 0;
            padding: 0;
            border: none;
            width: 100%;
        }

        .tw-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6mm;
            table-layout: fixed;
        }

        .tw-table thead {
            display: table-header-group;
        }

        .tw-table thead tr {
            background: var(--accent) !important;
            color: var(--tw-white) !important;
        }

        .tw-table thead th {
            padding: 1mm 0.5mm;
            font-size: 5.8pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            text-align: center;
            color: var(--tw-white) !important;
            background: var(--accent) !important;
            border: 1px solid var(--accent-dark);
            line-height: 1.12;
        }

        .tw-table thead th:nth-child(2) {
            text-align: left;
            padding-left: 1.5mm;
        }

        .tw-table thead th:nth-child(5),
        .tw-table thead th:nth-child(6) {
            text-align: right;
            padding-right: 1mm;
        }

        .tw-table tbody td {
            padding: 1.2mm 1mm;
            font-size: 6.5pt;
            border: 1px solid var(--tw-slate-200);
            vertical-align: top;
        }

        .tw-table tbody tr:nth-child(odd) {
            background: var(--tw-white);
        }

        .tw-table tbody tr:nth-child(even) {
            background: #eef6fc;
        }

        .tw-table tbody td:first-child {
            text-align: center;
            color: var(--tw-slate-500);
            font-weight: 600;
        }

        .tw-table tbody td:nth-child(2) {
            text-align: left;
            color: var(--heading);
            padding-left: 1.2mm;
        }

        .tw-table tbody td:nth-child(3),
        .tw-table tbody td:nth-child(4) {
            text-align: center;
            color: var(--tw-slate-600);
            text-transform: uppercase;
        }

        .tw-table tbody td:nth-child(5),
        .tw-table tbody td:nth-child(6) {
            text-align: right;
            color: var(--tw-slate-800);
        }

        .tw-table tbody td:nth-child(6) {
            font-weight: 600;
        }

        .tw-no-rows {
            text-align: center;
            font-style: italic;
            color: var(--tw-slate-400);
            padding: 5mm !important;
        }

        /* Totales */
        .tw-totals-wrap {
            width: 100%;
            page-break-inside: avoid;
        }

        .tw-totals-inner {
            width: 52%;
            margin-left: 48%;
            border: 1px solid var(--tw-slate-200);
            border-radius: 1mm;
            overflow: hidden;
            background: var(--tw-white);
        }

        .tw-totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .tw-totals-table td {
            padding: 1mm 2mm;
            font-size: 7pt;
        }

        .tw-totals-table td:first-child {
            text-align: right;
            color: var(--tw-slate-600);
            background: var(--tw-slate-50);
        }

        .tw-totals-table td:last-child {
            text-align: right;
            font-weight: 600;
            color: var(--tw-slate-800);
        }

        .tw-totals-table .tw-total-row td {
            background: var(--tw-white);
            border-top: 2px solid var(--accent);
            font-size: 10pt;
            font-weight: 700;
        }

        .tw-totals-table .tw-total-row td:first-child {
            color: var(--heading);
            text-transform: uppercase;
        }

        .tw-totals-table .tw-total-row td:last-child {
            color: var(--accent);
        }

        .after-table-space {
            height: 8mm;
        }

        /* Términos */
        .terms-block {
            margin-bottom: 3mm;
            page-break-inside: auto;
        }

        .tw-terms+.tw-terms {
            margin-top: 2mm;
        }

        .tw-terms {
            margin-top: 3mm;
            padding-top: 0;
            border-top: none;
        }

        .tw-terms h3 {
            font-size: 6.5pt;
            font-weight: 700;
            color: var(--heading);
            text-transform: none;
            letter-spacing: 0.02em;
            margin: 0 0 1mm 0;
            line-height: var(--section-line-height);
        }

        .tw-terms ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .tw-terms ul.tw-terms-num {
            counter-reset: twi;
        }

        .tw-terms ul.tw-terms-num li {
            font-size: 6.2pt;
            color: var(--tw-slate-600);
            line-height: var(--section-line-height);
            margin-bottom: 0.6mm;
            padding-left: 5mm;
            position: relative;
            text-align: justify;
        }

        .tw-terms ul.tw-terms-num li::before {
            counter-increment: twi;
            content: counter(twi) ".";
            position: absolute;
            left: 0;
            font-weight: 600;
            color: var(--tw-slate-800);
        }

        /* Observaciones: lista numerada (contador independiente de términos) */
        .tw-terms ul.tw-obs-list {
            counter-reset: twobs;
        }

        .tw-terms ul.tw-obs-list li {
            font-size: 6.2pt;
            color: var(--tw-slate-600);
            line-height: var(--section-line-height);
            margin-bottom: 0.6mm;
            padding-left: 5mm;
            position: relative;
            text-align: justify;
        }

        .tw-terms ul.tw-obs-list li::before {
            counter-increment: twobs;
            content: counter(twobs) ".";
            position: absolute;
            left: 0;
            font-weight: 600;
            color: var(--tw-slate-800);
        }

        /* Footer fijo (igual que plantilla clásica) */
        .footer {
            position: fixed;
            bottom: 6mm;
            left: {{ $margenMm }}mm;
            right: {{ $margenMm }}mm;
            height: {{ $footerHeightMm - 2 }}mm;
            min-height: {{ $footerHeightMm - 2 }}mm;
            padding: 1mm 0 2mm;
            font-size: 6.5pt;
            color: var(--tw-slate-500);
            line-height: 1.3;
            overflow: visible;
        }

        .footer-table {
            display: table;
            width: 100%;
        }

        .footer-left {
            display: table-cell;
            width: 33%;
            vertical-align: bottom;
        }

        .footer-center {
            display: table-cell;
            width: 34%;
            text-align: center;
            vertical-align: middle;
        }

        .footer-right {
            display: table-cell;
            width: 33%;
            text-align: right;
            vertical-align: middle;
            padding-left: 2mm;
        }

        .footer-logos-row {
            display: table;
        }

        .footer-logo-cell {
            display: table-cell;
            padding-right: 2.5mm;
            vertical-align: middle;
        }

        .footer-logo-cell:last-child {
            padding-right: 0;
        }

        .footer-logo-img {
            width: 12mm;
            height: 12mm;
            object-fit: contain;
            display: block;
        }

        .footer-logo-placeholder {
            width: 12mm;
            height: 12mm;
            min-width: 12mm;
            background: var(--tw-slate-200);
            border-radius: 1.5mm;
            font-size: 5pt;
            font-weight: 700;
            color: var(--tw-slate-500);
            text-align: center;
            line-height: 8mm;
        }

        .footer-center-content {
            text-align: center;
            width: 100%;
        }

        .footer-pages {
            font-weight: 600;
            color: var(--tw-slate-700);
            font-size: 7pt;
            margin-bottom: 0.6mm;
            min-height: 3mm;
        }

        .footer-slogan {
            font-style: italic;
            color: var(--accent);
            font-size: 6pt;
        }

        .footer-webs {
            font-size: 6pt;
        }

        .footer-webs-link {
            color: var(--accent);
            text-decoration: none;
        }

        .footer-webs-sep {
            color: var(--tw-slate-400);
            margin: 0 1mm;
        }

        .footer-qr {
            display: inline-block;
            width: 15mm;
            height: 15mm;
            vertical-align: middle;
        }

        .footer-qr img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        /* ===== DEBUG VISUAL (TW VERSION) ===== */

        /* .tw-header-wrap {
            outline: 2px solid red;
        }

        .tw-header-main {
            outline: 1px solid purple;
        }

        .tw-header-top {
            outline: 1px dashed gray;
        }

        .tw-logo-cell {
            outline: 2px solid blue;
        }

        .tw-emisor-cell {
            outline: 2px solid green;
        }

        .tw-folio-cell {
            outline: 2px solid orange;
        }

        .tw-header-rule {
            outline: 1px solid black;
        }

        .tw-card {
            outline: 2px solid purple;
        }

        .tw-desc-box {
            outline: 2px solid teal;
        }

        .tw-section-title {
            outline: 1px solid brown;
        }

        .tw-table {
            outline: 2px solid black;
        }

        .tw-table thead {
            outline: 2px solid red;
        }

        .tw-table tbody {
            outline: 2px solid blue;
        }

        .tw-totals-wrap {
            outline: 2px solid darkgreen;
        }

        .tw-totals-inner {
            outline: 2px dashed green;
        }

        .after-table-space {
            outline: 1px solid red;
        }
        .terms-block {
            outline: 2px solid magenta;
        }

        .tw-terms {
            outline: 1px solid pink;
        }

        .footer {
            outline: 2px dashed red;
        }

        .footer-left {
            outline: 2px solid blue;
        }

        .footer-center {
            outline: 2px solid green;
        }

        .footer-right {
            outline: 2px solid orange;
        } */
    </style>
</head>

<body>
    <div class="footer">
        <div class="footer-table">
            <div class="footer-left">
                @php
                    $logos = $presupuesto['logos_base64'] ?? [];
                    $appKeys = ['gestionpro'];
                @endphp
                <div class="footer-logos-row">
                    @foreach ($appKeys as $key)
                        <div class="footer-logo-cell">
                            @if (!empty($logos[$key]))
                                <img src="{{ $logos[$key] }}" alt="" class="footer-logo-img" />
                            @else
                                <span class="footer-logo-placeholder">{{ strtoupper(substr($key, 0, 1)) }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="footer-center">
                <div class="footer-center-content">
                    <div class="footer-slogan">"Calidad y compromiso en cada proyecto"</div>
                    <div class="footer-webs">
                        <a href="https://heventec.com" class="footer-webs-link" target="_blank">heventec.com</a><span
                            class="footer-webs-sep">|</span><a href="https://gestion.heventec.com/" target="_blank"
                            class="footer-webs-link">gestion.heventec.com</a>
                        <div class="footer-pages">&nbsp;</div>
                    </div>
                </div>
            </div>
            <div class="footer-right">
                @if (isset($presupuesto['qr_code']) && $presupuesto['qr_code'])
                    <div class="footer-qr">
                        <img src="{{ $presupuesto['qr_code'] }}" alt="Ver versión web" title="Ver versión web" />
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="margin-sides">
        <div class="document-container">
            <table class="tw-header-wrap" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="tw-header-main">
                        <table class="tw-header-top">
                            <tr>
                                <td class="tw-logo-cell">
                                    @php
                                        $logoProveedorBase64 = $presupuesto['logo_proveedor_base64'] ?? null;
                                        $nombreEmpresa =
                                            $presupuesto['proveedor']->razon_social ??
                                            ($presupuesto['proveedor']->nombre_comercial ?? 'P');
                                        $inicial = strtoupper(substr($nombreEmpresa, 0, 1));
                                    @endphp
                                    @if ($logoProveedorBase64)
                                        <img src="{{ $logoProveedorBase64 }}" alt="Logo" class="tw-logo-img" />
                                    @else
                                        <div class="tw-logo-fallback">{{ $inicial }}</div>
                                    @endif
                                </td>
                                <td class="tw-emisor-cell">
                                    @php
                                        $p = $presupuesto['proveedor'];
                                        $emisorNombre =
                                            $p->razon_social ??
                                            ($p->nombre_comercial ?? 'Empresa Proveedora S.A. de C.V.');
                                        $emisorRfc = $p->rfc ?? null;
                                        $emisorDireccion = $p->direccion_empresa ?? null;
                                        $df = $p->direccion_fiscal ?? null;
                                        $ciudad =
                                            $p->ciudad ??
                                            (is_array($df)
                                                ? $df['ciudad'] ?? 'Ciudad de México'
                                                : $df->ciudad ?? 'Ciudad de México');
                                        $estado = is_array($df) ? $df['estado'] ?? 'CDMX' : $df->estado ?? 'CDMX';
                                        $emisorCiudad = $ciudad . ', ' . $estado . ', México';
                                        $emisorTel = $p->telefono ?? null;
                                        $emisorEmail = $p->email ?? null;
                                    @endphp
                                    <div class="tw-emisor-name">{{ $emisorNombre }}</div>
                                    @if ($emisorRfc)
                                        <div class="tw-emisor-line">{{ $emisorRfc }}</div>
                                    @endif
                                    @if ($emisorDireccion)
                                        <div class="tw-emisor-line">{{ $emisorDireccion }}</div>
                                    @endif
                                    @if ($emisorCiudad)
                                        <div class="tw-emisor-line">{{ $emisorCiudad }}</div>
                                    @endif
                                    @if ($emisorTel)
                                        <div class="tw-emisor-line">Tel. {{ $emisorTel }}</div>
                                    @endif
                                    @if ($emisorEmail)
                                        <div class="tw-emisor-line">{{ $emisorEmail }}</div>
                                    @endif
                                </td>
                                <td class="tw-folio-cell">
                                    <div class="tw-badge-label">Presupuesto</div>
                                    <div class="tw-badge-folio">
                                        {{ $presupuesto['numero_presupuesto'] ?? 'PRES-000001' }}</div>
                                    @if (!empty($presupuesto['uuid']))
                                        <div class="tw-uuid">{{ $presupuesto['uuid'] }}</div>
                                    @endif
                                    <div class="tw-date">
                                        @php
                                            $fecha = $presupuesto['fecha_emision'] ?? now();
                                            if (is_string($fecha)) {
                                                $fecha = \Carbon\Carbon::parse($fecha);
                                            }
                                            $fechaFormateada = $fecha
                                                ->locale('es')
                                                ->translatedFormat('d \d\e F \d\e\l Y');
                                        @endphp
                                        {{ $fechaFormateada }}
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <div class="tw-header-rule" role="presentation"></div>

            <div class="tw-card">
                <div class="tw-card-title">Dirigido a:</div>
                @foreach ($presupuesto['receptor_lineas'] ?? [] as $idx => $linea)
                    <div class="{{ $idx === 0 ? 'tw-receptor-strong' : 'tw-receptor-line' }}">{{ $linea }}</div>
                @endforeach
            </div>

            @if ($presupuesto['concepto_general'] ?? null)
                <div class="tw-desc-box">
                    <div class="tw-desc-title">Descripción general</div>
                    <div class="tw-desc-text">{{ $presupuesto['concepto_general'] }}</div>
                </div>
            @endif

            <div class="tw-section-title">Presupuesto</div>
            <table class="tw-table">
                <thead>
                    <tr>
                        <th scope="col" style="width:5%;background:#3498db;color:#ffffff;">#</th>
                        <th scope="col" style="width:36%;background:#3498db;color:#ffffff;">Descripción</th>
                        <th scope="col" style="width:10%;background:#3498db;color:#ffffff;">Cantidad</th>
                        <th scope="col" style="width:10%;background:#3498db;color:#ffffff;">Unidad</th>
                        <th scope="col" style="width:19%;background:#3498db;color:#ffffff;">Precio unitario</th>
                        <th scope="col" style="width:20%;background:#3498db;color:#ffffff;">Importe</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $conceptos = $presupuesto['conceptos'] ?? [];
                        $subtotal = 0;
                    @endphp
                    @if (count($conceptos) > 0)
                        @foreach ($conceptos as $index => $concepto)
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
                            <td colspan="6" class="tw-no-rows">No hay conceptos registrados</td>
                        </tr>
                    @endif
                </tbody>
            </table>

            <div class="tw-totals-wrap">
                @php
                    $subtotalCalculado = $presupuesto['subtotal'] ?? $subtotal;
                    $conIva = $presupuesto['con_iva'] ?? false;
                    $ivaPorcentaje = $presupuesto['iva_porcentaje'] ?? 16;
                    $ivaTotal = $conIva ? $subtotalCalculado * ($ivaPorcentaje / 100) : 0;
                    $total = $subtotalCalculado + $ivaTotal;
                @endphp
                <div class="tw-totals-inner">
                    <table class="tw-totals-table">
                        <tr>
                            <td>Subtotal</td>
                            <td>${{ number_format($subtotalCalculado, 2, '.', ',') }}</td>
                        </tr>
                        @if ($conIva)
                            <tr>
                                <td>IVA ({{ number_format($ivaPorcentaje, 0) }}%)</td>
                                <td>${{ number_format($ivaTotal, 2, '.', ',') }}</td>
                            </tr>
                        @endif
                        <tr class="tw-total-row">
                            <td>Total</td>
                            <td>${{ number_format($total, 2, '.', ',') }}</td>
                        </tr>
                    </table>
                </div>
                <div class="after-table-space"></div>
            </div>

            <div class="terms-block">
                @if (count($terminosLista) > 0)
                    <div class="tw-terms">
                        <h3>Términos y Condiciones</h3>
                        <ul class="tw-terms-num">
                            @foreach ($terminosLista as $texto)
                                <li>{{ $texto }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if (count($observacionesLista) > 0)
                    <div class="tw-terms">
                        <h3>Observaciones Generales</h3>
                        <ul class="tw-obs-list">
                            @foreach ($observacionesLista as $obs)
                                <li>{{ $obs }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script type="text/php">
        if (isset($pdf) && isset($fontMetrics)) {
            $text = "Página {PAGE_NUM} de {PAGE_COUNT}";
            $size = 7;
            $font = $fontMetrics->getFont("DejaVu Sans", "normal");
            $sample = "Página 99 de 99";
            $width = $fontMetrics->getTextWidth($sample, $font, $size);
            $x = ($pdf->get_width() - $width) / 2 + 5;
            $y = $pdf->get_height() - 45;
            $pdf->page_text($x, $y, $text, $font, $size);
        }
    </script>
</body>

</html>
