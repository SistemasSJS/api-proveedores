@php
    use App\Helpers\PresupuestoCondicionesHelper;

    $margenMm = 18;
    $footerHeightMm = 24; // Espacio reservado para pie de página en cada hoja (carta)
    $conIvaPdf = $presupuesto['con_iva'] ?? false;
    $ivaPct = (float) ($presupuesto['iva_porcentaje'] ?? 16);

    $terminosLista = PresupuestoCondicionesHelper::buildTerminosLista(
        $presupuesto['condiciones'] ?? null,
        $conIvaPdf,
        $ivaPct,
    );

    $observacionesLista = PresupuestoCondicionesHelper::buildObservacionesLista(
        $presupuesto['condiciones'] ?? null,
        $presupuesto['observaciones'] ?? null,
    );

    // if (!empty($presupuesto['proveedor']->datos_bancarios ?? null)) {
    //     $bancarios = is_string($presupuesto['proveedor']->datos_bancarios)
    //         ? json_decode($presupuesto['proveedor']->datos_bancarios, true)
    //         : $presupuesto['proveedor']->datos_bancarios;
    //     if ($bancarios) {
    //         $observacionesLista[] =
    //             'Datos bancarios: Banco ' .
    //             ($bancarios['banco'] ?? 'N/A') .
    //             ', CLABE ' .
    //             ($bancarios['clabe_interbancaria'] ?? 'N/A') .
    //             ', Cuenta ' .
    //             ($bancarios['numero_cuenta'] ?? 'N/A') .
    //             '.';
    //     }
    // }

@endphp
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Presupuesto {{ $presupuesto['numero_presupuesto'] ?? 'N/A' }}</title>
    <style>
        @page {
            size: letter;
            margin-top: {{ $margenMm + 1000 }}mm;
            margin-left: {{ $margenMm }}mm;
            margin-right: {{ $margenMm }}mm;
            margin-bottom: {{ $footerHeightMm + 2 }}mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        html,
        body {
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

        .document-main {
            margin-bottom: 6mm;
        }

        .terms-block {
            margin-top: 0;
            margin-bottom: 4mm;
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
            /* padding-right: 2mm; */
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
            /* max-height: 16mm; */
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
            font-size: 7.5pt;
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
            /* font-size: 14pt; */
            font-size: 9pt;
            /* igual al tamaño de font del importe total*/
            font-weight: 700;
            color: #3498db;
            margin-bottom: 0.8mm;
            letter-spacing: -0.5pt;
            word-wrap: break-word;
            line-height: 1.1;
            overflow: hidden;
        }

        .folio-uuid {
            font-size: 5.5pt;
            color: #7f8c8d;
            margin-bottom: 0.6mm;
            line-height: 1.1;
            word-break: break-all;
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
            /* font-size: 7pt;
            color: #3498db;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 2mm;
            padding-bottom: 1mm;
            border-bottom: 2px solid #3498db;
            display: inline-block;
            line-height: 1.1; */
            font-size: 6pt;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            margin-bottom: 0.8mm;
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
            margin-top: 4mm;
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

        .presupuesto-table thead td:first-child {
            width: 5%;
        }

        .presupuesto-table thead td:nth-child(2) {
            width: 38%;
            text-align: left;
            padding-left: 1.5mm;
        }

        .presupuesto-table thead td:nth-child(3) {
            width: 10%;
        }

        .presupuesto-table thead td:nth-child(4) {
            width: 10%;
        }

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

        /* ========== 6) TÉRMINOS Y CONDICIONES (al final de la última página) ========== */
        .terminos-section {
            margin-top: 2mm;
            padding: 3mm 4mm;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 2mm;
        }

        .terminos-main-title {
            font-size: 7pt;
            font-weight: 600;
            color: #4b5563;
            margin-bottom: 1.5mm;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
        }

        .terminos-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .terminos-list li {
            font-size: 5.8pt;
            /* ligeramente más compacto */
            color: #6b7280;
            line-height: 1.05;
            /* MÁS PEGADO */
            margin-bottom: 0.6mm;
            /* menos separación entre items */
        }

        /* .terminos-section {
            margin-bottom: 0;
            margin-top: 0;
            padding: 4mm 6mm;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 2mm;
        }

        .terminos-main-title {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 8pt;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 2mm;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
            line-height: 1.2;
        }

        .terminos-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .terminos-list li {
            font-size: 7pt;
            color: #374151;
            line-height: 1.5;
            margin-bottom: 1.5mm;
            padding-left: 0;
        }

        .terminos-list .termino-num {
            font-weight: 700;
            color: #2c3e50;
        } */

        /* ========== 7) OBSERVACIONES GENERALES ========== */
        .observaciones-section {
            margin-bottom: 0;
            margin-top: 3mm;
            padding: 4mm 6mm;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 2mm;
        }

        .observaciones-title {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 8pt;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 2mm;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
            line-height: 1.2;
        }

        .observaciones-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .observaciones-list li {
            font-size: 7pt;
            color: #374151;
            line-height: 1.5;
            margin-bottom: 1.5mm;
            padding-left: 4mm;
            position: relative;
        }

        .observaciones-list li::before {
            content: "•";
            position: absolute;
            left: 0;
            color: #2c3e50;
            font-weight: bold;
        }

        /* ========== 8) PIE DE PÁGINA (ancho = página - márgenes, logos izq, centro, QR derecha) ========== */
        .footer {
            position: fixed;
            bottom: 0mm;
            left: {{ $margenMm }}mm;
            right: {{ $margenMm }}mm;
            height: {{ $footerHeightMm - 2 }}mm;
            min-height: {{ $footerHeightMm - 2 }}mm;
            padding: 1mm 0 2mm;
            font-size: 5.5pt;
            color: #6b7280;
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
            vertical-align: middle;
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
            background: #e5e7eb;
            border-radius: 1.5mm;
            font-size: 5pt;
            font-weight: 700;
            color: #6b7280;
            text-align: center;
            line-height: 8mm;
        }

        .footer-center-content {
            text-align: center;
            width: 100%;
        }

        .footer-pages {
            font-weight: 600;
            color: #374151;
            font-size: 6pt;
            margin-bottom: 0.6mm;
            min-height: 3mm;
        }

        .footer-slogan {
            font-style: italic;
            color: #6b7280;
            font-size: 5pt;
            margin-bottom: 0.4mm;
        }

        .footer-webs {
            font-size: 5pt;
        }

        .footer-webs-link {
            color: #2563eb;
            text-decoration: none;
        }

        .footer-webs-sep {
            color: #9ca3af;
            margin: 0 1mm;
        }

        .footer-qr {
            display: inline-block;
            width: 20mm;
            height: 20mm;
            vertical-align: middle;
        }

        .footer-qr img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .page-break-avoid {
            page-break-inside: avoid;
        }
    </style>
</head>

<body>
    {{-- Pie de página: logos izq, centro: Página N de N + slogan + urls, QR derecha --}}
    <div class="footer">
        <div class="footer-table">
            <div class="footer-left">
                @php
                    $logos = $presupuesto['logos_base64'] ?? [];
                    $appKeys = ['facturapro', 'constucc', 'gestionpro'];
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
                    <div class="footer-pages">&nbsp;</div>
                    <div class="footer-slogan">"Calidad y compromiso en cada proyecto"</div>
                    <div class="footer-webs">
                        <a href="https://heventec.com" class="footer-webs-link">heventec.com</a><span
                            class="footer-webs-sep">|</span><a href="https://gestionpro.com"
                            class="footer-webs-link">gestionpro.com</a>
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

    <div class="margin-top"></div>
    <div class="margin-sides">
        <div class="document-container">
            <div class="document-main">
                <!-- 1) ENCABEZADO -->
                <div class="header">
                    <table class="header-content">
                        <tr>
                            <td class="logo-section">
                                @php
                                    $logoProveedorBase64 =
                                        $presupuesto['condiciones']['emisor_logo'] ?? null ?:
                                        $presupuesto['logo_proveedor_base64'] ?? null;
                                    $nombreEmpresa =
                                        $presupuesto['proveedor']->razon_social ??
                                        ($presupuesto['proveedor']->nombre_comercial ?? 'P');
                                    $inicial = strtoupper(substr($nombreEmpresa, 0, 1));
                                @endphp
                                @if ($logoProveedorBase64)
                                    <img src="{{ $logoProveedorBase64 }}" alt="Logo" class="logo-img" />
                                @else
                                    <div class="logo-fallback">{{ $inicial }}</div>
                                @endif
                            </td>
                            <td class="header-info">
                                @php
                                    $emisorNombre =
                                        $presupuesto['condiciones']['emisor_razon_social'] ??
                                        ($presupuesto['proveedor']->razon_social ??
                                            ($presupuesto['proveedor']->nombre_comercial ??
                                                'Empresa Proveedora S.A. de C.V.'));
                                    $emisorRfc =
                                        $presupuesto['condiciones']['emisor_rfc'] ?? $presupuesto['proveedor']->rfc;
                                    $emisorDireccion =
                                        $presupuesto['condiciones']['emisor_direccion'] ??
                                        $presupuesto['proveedor']->direccion_empresa;
                                    $emisorCiudad = $presupuesto['condiciones']['emisor_ciudad_estado'] ?? null;
                                    if (!$emisorCiudad) {
                                        $df = $presupuesto['proveedor']->direccion_fiscal ?? null;
                                        $ciudad =
                                            $presupuesto['proveedor']->ciudad ??
                                            ($df ? $df->ciudad ?? 'Ciudad de México' : 'Ciudad de México');
                                        $estado = $df ? $df->estado ?? 'CDMX' : 'CDMX';
                                        $emisorCiudad = $ciudad . ', ' . $estado . ', México';
                                    }
                                    $emisorTel =
                                        $presupuesto['condiciones']['emisor_telefono'] ??
                                        $presupuesto['proveedor']->telefono;
                                    $emisorEmail =
                                        $presupuesto['condiciones']['emisor_email'] ?? $presupuesto['proveedor']->email;
                                @endphp
                                <div class="company-header-name">{{ $emisorNombre }}</div>
                                @if ($emisorRfc)
                                    <div class="company-header-info">{{ $emisorRfc }}</div>
                                @endif
                                @if ($emisorDireccion)
                                    <div class="company-header-info">{{ $emisorDireccion }}</div>
                                @endif
                                @if ($emisorCiudad)
                                    <div class="company-header-info">{{ $emisorCiudad }}</div>
                                @endif
                                @if ($emisorTel)
                                    <div class="company-header-info">Tel. {{ $emisorTel }}</div>
                                @endif
                                @if ($emisorEmail)
                                    <div class="company-header-info">{{ $emisorEmail }}</div>
                                @endif
                            </td>
                            <td class="folio-section">
                                <div class="folio-label">Presupuesto</div>
                                <div class="folio-number">{{ $presupuesto['numero_presupuesto'] ?? 'PRES-000001' }}
                                </div>
                                @if (!empty($presupuesto['uuid']))
                                    <div class="folio-uuid">{{ $presupuesto['uuid'] }}</div>
                                @endif
                                <div class="folio-date">
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
                </div>

                <!-- 2) DATOS DEL RECEPTOR -->
                <div class="receptor-section">
                    <div class="receptor-title">Datos del receptor</div>

                    @php
                        $receptor = $presupuesto['empresa_receptora'] ?? [];

                        $nombreCorto = $receptor['empresa'] ?? null;
                        $nombre = $receptor['nombre'] ?? null;
                        $puesto = $receptor['puesto'] ?? null;
                        $empresa = $receptor['empresa'] ?? null;
                        $correo = $receptor['correo'] ?? null;
                        $telefono = $receptor['telefono'] ?? null;
                        $direccion = $receptor['direccion'] ?? null;
                    @endphp

                    {{-- 1. Nombre corto --}}
                    @if ($nombreCorto)
                        <div class="receptor-name">{{ $nombreCorto }}</div>
                    @endif

                    {{-- 2. Nombre de la persona --}}
                    @if ($nombre)
                        <div class="receptor-info">{{ $nombre }}</div>
                    @endif

                    {{-- 3. Cargo --}}
                    @if ($puesto)
                        <div class="receptor-info"></strong> {{ $puesto }}</div>
                    @endif

                    {{-- 4. Empresa --}}
                    @if ($empresa)
                        <div class="receptor-info">{{ $empresa }}</div>
                    @endif

                    {{-- Extras --}}
                    {{-- @if ($correo)<div class="receptor-info">{{ $correo }}</div>@endif --}}
                    {{-- @if ($telefono)<div class="receptor-info">{{ $telefono }}</div>@endif --}}
                    {{-- @if ($direccion)<div class="receptor-info">{{ $direccion }}</div>@endif --}}
                </div>

                <!-- 3) DESCRIPCIÓN GENERAL -->
                @if ($presupuesto['concepto_general'] ?? null)
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
                        $ivaTotal = $conIva ? $subtotalCalculado * ($ivaPorcentaje / 100) : 0;
                        $total = $subtotalCalculado + $ivaTotal;
                    @endphp
                    <table class="totales-table">
                        <tr>
                            <td>Subtotal:</td>
                            <td>${{ number_format($subtotalCalculado, 2, '.', ',') }}</td>
                        </tr>
                        @if ($conIva)
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

            </div>
            <div class="terms-block">
                <!-- 6) TÉRMINOS Y CONDICIONES (listado como preview) -->
                @if (count($terminosLista) > 0)
                    <div class="terminos-section">
                        <div class="terminos-main-title">Términos y Condiciones</div>
                        <ul class="terminos-list">
                            @foreach ($terminosLista as $item)
                                <li>{{ $item['texto'] }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- 7) OBSERVACIONES GENERALES (listado como preview) -->
                @if (count($observacionesLista) > 0)
                    <div class="terminos-section">
                        <div class="terminos-title">Observaciones Generales</div>
                        <ul class="terminos-list">
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
            $sample = "Página 1 de 1";
            $width = $fontMetrics->getTextWidth($sample, $font, $size);
            $x = ($pdf->get_width() - $width) / 2;
            $y = $pdf->get_height() - 22;
            $pdf->page_text($x, $y, $text, $font, $size);
        }
    </script>
</body>

</html>
