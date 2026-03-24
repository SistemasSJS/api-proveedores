@php
    $margenMm = 25.4;
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
      Plantilla PDF alternativa (estilo Tailwind: paleta slate + indigo).
      DomPDF no carga CDN remoto: utilidades expresadas como CSS embebido.
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
            --tw-indigo-50: #eef2ff;
            --tw-indigo-100: #e0e7ff;
            --tw-indigo-500: #6366f1;
            --tw-indigo-600: #4f46e5;
            --tw-indigo-700: #4338ca;
            --tw-white: #ffffff;
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

        /* —— Encabezado: barra lateral + rejilla —— */
        .tw-header-wrap {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4mm;
            page-break-inside: avoid;
            border: 1px solid var(--tw-slate-200);
            border-radius: 3mm;
            overflow: hidden;
        }

        .tw-accent {
            width: 4mm;
            background: linear-gradient(180deg, var(--tw-indigo-600), var(--tw-indigo-700));
        }

        .tw-header-main {
            vertical-align: top;
            padding: 3mm 4mm;
            background: var(--tw-slate-50);
        }

        .tw-header-top {
            width: 100%;
            border-collapse: collapse;
        }

        .tw-logo-cell {
            width: 18%;
            vertical-align: top;
        }

        .tw-logo-img {
            max-width: 100%;
            max-height: 18mm;
            object-fit: contain;
            border-radius: 2mm;
        }

        .tw-logo-fallback {
            width: 14mm;
            height: 14mm;
            background: var(--tw-indigo-600);
            border-radius: 2mm;
            text-align: center;
            line-height: 14mm;
            color: var(--tw-white);
            font-size: 11pt;
            font-weight: bold;
        }

        .tw-emisor-cell {
            vertical-align: top;
            padding-left: 3mm;
            width: 52%;
        }

        .tw-emisor-name {
            font-size: 9.5pt;
            font-weight: 700;
            color: var(--tw-slate-900);
            margin-bottom: 1mm;
        }

        .tw-emisor-line {
            font-size: 7pt;
            color: var(--tw-slate-600);
            margin-bottom: 0.5mm;
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
            color: var(--tw-indigo-600);
            margin-bottom: 1mm;
        }

        .tw-badge-folio {
            display: inline-block;
            background: var(--tw-indigo-50);
            border: 1px solid var(--tw-indigo-100);
            color: var(--tw-indigo-700);
            font-size: 10pt;
            font-weight: 700;
            padding: 1.5mm 3mm;
            border-radius: 2mm;
            margin-bottom: 1mm;
        }

        .tw-uuid {
            font-size: 6pt;
            color: var(--tw-slate-500);
            word-break: break-all;
            max-width: 100%;
        }

        .tw-date {
            font-size: 7pt;
            color: var(--tw-slate-700);
            margin-top: 1mm;
        }

        /* Receptor */
        .tw-card {
            margin-bottom: 4mm;
            padding: 3mm 4mm;
            background: var(--tw-white);
            border: 1px solid var(--tw-slate-200);
            border-left: 4px solid var(--tw-indigo-500);
            border-radius: 2mm;
            page-break-inside: avoid;
        }

        .tw-card-title {
            font-size: 6.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--tw-indigo-600);
            margin-bottom: 2mm;
        }

        .tw-receptor-strong {
            font-size: 9pt;
            font-weight: 700;
            color: var(--tw-slate-900);
            margin-bottom: 0.8mm;
        }

        .tw-receptor-line {
            font-size: 7pt;
            color: var(--tw-slate-600);
            margin-bottom: 0.5mm;
        }

        /* Descripción */
        .tw-desc-box {
            margin-bottom: 4mm;
            padding: 3mm 4mm;
            background: var(--tw-slate-50);
            border-radius: 2mm;
            border: 1px dashed var(--tw-slate-200);
            page-break-inside: avoid;
        }

        .tw-desc-title {
            font-size: 6.5pt;
            font-weight: 700;
            color: var(--tw-slate-700);
            margin-bottom: 1.5mm;
        }

        .tw-desc-text {
            font-size: 7pt;
            color: var(--tw-slate-600);
            text-align: justify;
            line-height: 1.35;
        }

        /* Tabla conceptos */
        .tw-section-title {
            font-size: 10pt;
            font-weight: 700;
            color: var(--tw-slate-900);
            margin: 5mm 0 2mm 0;
            padding-bottom: 1mm;
            border-bottom: 2px solid var(--tw-indigo-100);
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
            background: var(--tw-slate-800);
            color: var(--tw-white);
        }

        .tw-table thead td {
            padding: 2mm 1.5mm;
            font-size: 6pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            text-align: center;
            border: 1px solid var(--tw-slate-700);
        }

        .tw-table thead td:nth-child(2) {
            text-align: left;
        }

        .tw-table thead td:nth-child(5),
        .tw-table thead td:nth-child(6) {
            text-align: right;
        }

        .tw-table tbody td {
            padding: 1.5mm 1.5mm;
            font-size: 6.5pt;
            border: 1px solid var(--tw-slate-200);
            vertical-align: top;
        }

        .tw-table tbody tr:nth-child(even) {
            background: var(--tw-slate-50);
        }

        .tw-table tbody td:first-child {
            text-align: center;
            color: var(--tw-slate-500);
            font-weight: 600;
        }

        .tw-table tbody td:nth-child(2) {
            text-align: left;
            color: var(--tw-slate-800);
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
            width: 55%;
            margin-left: 45%;
            border: 1px solid var(--tw-slate-200);
            border-radius: 2mm;
            overflow: hidden;
        }

        .tw-totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .tw-totals-table td {
            padding: 2mm 3mm;
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
            background: var(--tw-indigo-50);
            border-top: 2px solid var(--tw-indigo-500);
            font-size: 9pt;
            font-weight: 700;
            color: var(--tw-indigo-700);
        }

        .after-table-space {
            height: {{ $footerHeightMm + 5 }}mm;
        }

        /* Términos */
        .terms-block {
            margin-bottom: 4mm;
            page-break-inside: auto;
        }

        .tw-terms {
            margin-top: 4mm;
            padding-top: 3mm;
            border-top: 1px solid var(--tw-slate-200);
        }

        .tw-terms h3 {
            font-size: 8pt;
            font-weight: 700;
            color: var(--tw-slate-900);
            margin: 0 0 2mm 0;
        }

        .tw-terms ul {
            list-style: none;
            padding: 0;
            margin: 0;
            counter-reset: twi;
        }

        .tw-terms li {
            font-size: 6.2pt;
            color: var(--tw-slate-600);
            line-height: 1.15;
            margin-bottom: 1mm;
            padding-left: 5mm;
            position: relative;
            text-align: justify;
        }

        .tw-terms li::before {
            counter-increment: twi;
            content: counter(twi) ".";
            position: absolute;
            left: 0;
            font-weight: 600;
            color: var(--tw-indigo-500);
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
            width: 8mm;
            height: 8mm;
            object-fit: contain;
            display: block;
        }

        .footer-logo-placeholder {
            width: 8mm;
            height: 8mm;
            min-width: 8mm;
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
            color: var(--tw-slate-500);
            font-size: 6pt;
        }

        .footer-webs {
            font-size: 6pt;
        }

        .footer-webs-link {
            color: var(--tw-indigo-600);
            text-decoration: none;
        }

        .footer-webs-sep {
            color: var(--tw-slate-400);
            margin: 0 1mm;
        }

        .footer-qr {
            display: inline-block;
            width: 12mm;
            height: 12mm;
            vertical-align: middle;
        }

        .footer-qr img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
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
                        <a href="https://heventec.com" class="footer-webs-link">heventec.com</a><span
                            class="footer-webs-sep">|</span><a href="https://gestionpro.com"
                            class="footer-webs-link">gestionpro.com</a>
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
                    <td class="tw-accent"></td>
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
                                    <div class="tw-badge-folio">{{ $presupuesto['numero_presupuesto'] ?? 'PRES-000001' }}</div>
                                    @if (!empty($presupuesto['uuid']))
                                        <div class="tw-uuid">{{ $presupuesto['uuid'] }}</div>
                                    @endif
                                    <div class="tw-date">
                                        @php
                                            $fecha = $presupuesto['fecha_emision'] ?? now();
                                            if (is_string($fecha)) {
                                                $fecha = \Carbon\Carbon::parse($fecha);
                                            }
                                            $fechaFormateada = $fecha->locale('es')->translatedFormat('d \d\e F \d\e\l Y');
                                        @endphp
                                        {{ $fechaFormateada }}
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <div class="tw-card">
                <div class="tw-card-title">Dirigido a</div>
                @php
                    $receptor = $presupuesto['empresa_receptora'] ?? [];
                    $aliasEmpresa = $receptor['alias_empresa'] ?? null;
                    $nombre = $receptor['nombre'] ?? null;
                    $empresa = $receptor['empresa'] ?? null;
                    $puesto = $receptor['puesto'] ?? null;
                @endphp
                @if ($aliasEmpresa)
                    <div class="tw-receptor-strong">{{ $aliasEmpresa }}</div>
                @endif
                @if ($nombre)
                    <div class="tw-receptor-line">{{ $nombre }}</div>
                @endif
                @if ($puesto)
                    <div class="tw-receptor-line">{{ $puesto }}</div>
                @endif
                @if ($empresa)
                    <div class="tw-receptor-line">{{ $empresa }}</div>
                @endif
            </div>

            @if ($presupuesto['concepto_general'] ?? null)
                <div class="tw-desc-box">
                    <div class="tw-desc-title">Descripción general</div>
                    <div class="tw-desc-text">{{ $presupuesto['concepto_general'] }}</div>
                </div>
            @endif

            <div class="tw-section-title">Detalle de conceptos</div>
            <table class="tw-table">
                <thead>
                    <tr>
                        <td style="width:6%;">#</td>
                        <td style="width:36%;">Descripción</td>
                        <td style="width:12%;">Cant.</td>
                        <td style="width:12%;">Unidad</td>
                        <td style="width:17%;">P. unitario</td>
                        <td style="width:17%;">Importe</td>
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

            <div class="page-break"></div>
            <div class="terms-block">
                @if (count($terminosLista) > 0)
                    <div class="tw-terms">
                        <h3>Términos y condiciones</h3>
                        <ul>
                            @foreach ($terminosLista as $texto)
                                <li>{{ $texto }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if (count($observacionesLista) > 0)
                    <div class="tw-terms">
                        <h3>Observaciones generales</h3>
                        <ul>
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
