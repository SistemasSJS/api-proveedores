        @php
            $margenMm = 25.4;
            $footerHeightMm = 25.4; // Espacio reservado para pie de página en cada hoja (carta)
            $terminosLista = $presupuesto['terminos_enunciados'] ?? [];
            $observacionesLista = $presupuesto['observaciones_enunciados'] ?? [];
        @endphp
        <!DOCTYPE html>
        <html lang="es">

        <head>
            <meta charset="UTF-8">
            <title>Presupuesto {{ $presupuesto['numero_presupuesto'] ?? 'N/A' }}</title>
            <style>
                @page {
                    size: letter;
                    margin: 25.5mm;
                    /* margin-left: {{ $margenMm }}mm;
                    margin-right: {{ $margenMm }}mm;
                    margin-bottom: {{ $footerHeightMm + 8 }}mm; */
                }

                .page-top-spacing {
                    padding-top: {{ $margenMm }}mm;
                }

                /*
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                } */

                .content-wrapper {
                    min-height: calc(100vh - {{ $footerHeightMm + 20 }}mm);
                    display: flex;
                    flex-direction: column;
                }

                html,
                body {
                    font-family: 'DejaVu Sans', Arial, sans-serif;
                    font-size: 8.5pt;
                    color: #171a1d;
                    background: #ffffff;
                    line-height: 1.15;
                    margin: 0;
                    padding: 0;
                    padding-bottom: {{ $footerHeightMm }}mm;
                    /* 🔥 clave */
                }

                body {
                    font-family: 'DejaVu Sans', Arial, sans-serif;
                    font-size: 8.5pt;
                    color: #171a1d;
                    background: #ffffff;
                    line-height: 1.15;
                    /* margin: 0; */
                    padding-top: {{ $margenMm }}mm;
                }

                /* Elementos de margen (cuando @page margin no funciona) */


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


                /* ========== 1) ENCABEZADO (igual que preview) ========== */
                .header {
                    margin-bottom: 4mm;
                    padding-bottom: 3mm;
                    /* border-bottom: 2px solid #3498db; */
                    border-bottom: 1px solid #d1d5db;
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
                    font-size: 6pt;
                    color: #171a1d;
                    margin-bottom: 0.6mm;
                    line-height: 1.1;
                    word-break: break-all;
                }

                .folio-date {
                    font-size: 7pt;
                    color: #171a1d;
                    line-height: 1.15;
                }

                /* ========== 2) DATOS DEL RECEPTOR (igual que preview) ========== */
                .receptor-section {
                    width: 100%;
                    margin-bottom: 3mm;
                    padding-bottom: 2mm;
                    /* border-bottom: 1.5px098 solid #3498db; */
                    page-break-inside: avoid;
                }

                .receptor-title {
                    font-size: 6pt;
                    color: #3498db;
                    font-weight: 700;
                    margin-bottom: 1mm;
                    text-transform: uppercase;
                }

                .receptor-name {
                    font-size: 9pt;
                    font-weight: 700;
                    color: #2c3e50;
                    margin-bottom: 1mm;
                    line-height: 1.15;
                }

                p .receptor-info {
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
                    font-size: 9pt;
                    font-weight: 700;
                    color: #2c3e50;
                    margin-top: 10mm;
                    margin-bottom: 2mm;
                    line-height: 1.15;
                    text-align: center;
                }

                .presupuesto-table {
                    width: 100%;
                    max-width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 10mm;
                    table-layout: fixed;
                    overflow: hidden;
                }

                /*
                .presupuesto-table tr {
                    page-break-inside: avoid;
                } */

                .presupuesto-table thead {
                    display: table-header-group;
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
                .terms-block {
                    margin-bottom: 4mm;
                    page-break-inside: avoid;
                    page-break-before: auto;
                }

                .totales-section {
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
                    margin-top: 5mm;
                    padding-top: 2mm;
                    border-top: 1px solid #d1d5db;
                    page-break-inside: auto;

                }

                .terminos-section-line {}

                .terminos-main-title,
                .terminos-title {
                    font-size: 7.5pt;
                    font-weight: 700;
                    color: #2c3e50;
                    margin-bottom: 1.5mm;
                    page-break-after: avoid;
                }

                .terminos-list {
                    list-style: none;
                    padding: 0;
                    margin: 0;
                    counter-reset: item;
                }

                .terminos-list li {
                    font-size: 6.2pt;
                    color: #4b5563;
                    line-height: 1.08;
                    /* 🔥 más compacto */
                    margin-bottom: 0.5mm;
                    /* 🔥 menos espacio */
                    padding-left: 4.5mm;
                    position: relative;
                    text-align: justify;
                }

                .terminos-list li::before {
                    counter-increment: item;
                    content: counter(item) ".";
                    position: absolute;
                    left: 0;
                    top: 0;
                    font-weight: 600;
                    color: #6b7280;
                    /* 🔥 más discreto */
                }

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
                    bottom: 6mm;
                    left: {{ $margenMm }}mm;
                    right: {{ $margenMm }}mm;
                    height: {{ $footerHeightMm - 2 }}mm;
                    min-height: {{ $footerHeightMm - 2 }}mm;
                    padding: 1mm 0 2mm;
                    font-size: 6.5pt;
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
                    vertical-align: bottom;
                    /* 🔥 clave */
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
                    font-size: 7pt;
                    margin-bottom: 0.6mm;
                    min-height: 3mm;
                }

                .footer-slogan {
                    font-style: italic;
                    color: #6b7280;
                    font-size: 6pt;
                    /* margin-bottom: 0.4mm; */
                }

                .footer-webs {
                    font-size: 6pt;
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
                    width: 12mm;
                    height: 12mm;
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

                .after-table-space {
                    height: {{ $footerHeightMm + 5 }}mm;
                }

                /* .tabla-wrapper {
                    max-height: 140mm;
                    overflow: hidden; */
                /* } */

                /* .page-break {
                    page-break-before: always;
                } */

                /* DEBUG VISUAL — quitar después */
                /* .header {
                    border: 1px solid red;
                }

                .receptor-section {
                    border: 1px solid blue;
                }

                .descripcion-section {
                    border: 1px solid green;
                }

                .presupuesto-title {
                    border: 1px dashed purple;
                }

                .tabla-wrapper {
                    border: 2px solid orange;
                }

                .presupuesto-table {
                    border: 1px solid brown;
                }

                .totales-section {
                    border: 2px solid cyan;
                }

                .terms-block {
                    border: 2px solid magenta;
                }

                .terminos-section {
                    border: 1px solid black;
                }

                .footer {
                    border: 2px solid gray;
                }

                .margin-sides {
                    border: 2px dashed pink;
                }

                body {
                    border: 3px solid lime;
                } */
            </style>
        </head>

        <body>
            {{-- Pie de página: logos izq, centro: Página N de N + slogan + urls, QR derecha --}}
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
                                        <span
                                            class="footer-logo-placeholder">{{ strtoupper(substr($key, 0, 1)) }}</span>
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
                                <img src="{{ $presupuesto['qr_code'] }}" alt="Ver versión web"
                                    title="Ver versión web" />
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="margin-sides">
                <div class="document-container">
                    <div class="document-main">
                        <!-- 1) ENCABEZADO -->
                        <div class="header">
                            <table class="header-content">
                                <tr>
                                    <td class="logo-section">
                                        @php
                                            $logoProveedorBase64 = $presupuesto['logo_proveedor_base64'] ?? null;
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
                                        <div class="folio-number">
                                            {{ $presupuesto['numero_presupuesto'] ?? 'PRES-000001' }}
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
                                                $fechaFormateada = $fecha
                                                    ->locale('es')
                                                    ->translatedFormat('d \d\e F \d\e\l Y');
                                            @endphp
                                            {{ $fechaFormateada }}
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- 2) DATOS DEL RECEPTOR -->
                        <div class="receptor-section">
                            <div class="receptor-title">Dirigido a:</div>

                            @php
                                $receptor = $presupuesto['empresa_receptora'] ?? [];
                                $alias_empresa = $receptor['alias_empresa'] ?? null;
                                $nombre = $receptor['nombre'] ?? null;
                                $empresa = $receptor['empresa'] ?? null;
                                $puesto = $receptor['puesto'] ?? null;
                                $aliasEmpresa = $receptor['alias_empresa'] ?? null;
                                // $telefono = $receptor['telefono'] ?? null;
                                // $correo = $receptor['correo'] ?? null;
                                // $direccion = $receptor['direccion'] ?? null;
                            @endphp

                            {{-- Alias de la emp (opc) --}}
                            {{-- Nombre corto --}}

                            @if ($aliasEmpresa)
                                <div class="receptor-name">{{ $aliasEmpresa }}</div>
                            @endif

                            {{-- Nombre de la persona --}}
                            @if ($nombre)
                                <div class="receptor-info">{{ $nombre }}</div>
                            @endif

                            {{-- Cargo --}}
                            @if ($puesto)
                                <div class="receptor-info">{{ $puesto }}</div>
                            @endif

                            {{-- Empresa --}}
                            @if ($empresa)
                                <div class="receptor-info">{{ $empresa }}</div>
                            @endif

                            {{-- @if ($telefono)
                                <div class="receptor-info">{{ $telefono }}</div>
                            @endif
                            @if ($correo)
                                <div class="receptor-info">{{ $correo }}</div>
                            @endif
                            @if ($direccion)
                                <div class="receptor-info">{{ $direccion }}</div>
                            @endif --}}
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
                                        <td colspan="6" class="no-conceptos">No hay conceptos registrados
                                        </td>
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
                            <div class="after-table-space"></div>
                        </div>

                    </div>
                    <div class="page-break"></div>
                    <div class="terms-block">
                        <!-- 6) TÉRMINOS Y CONDICIONES (listado como preview) -->
                        @if (count($terminosLista) > 0)
                            <div class="terminos-section
                        terminos-section-line">
                                <div class="terminos-main-title">Términos y Condiciones</div>
                                <ul class="terminos-list">
                                    @foreach ($terminosLista as $texto)
                                        <li>{{ $texto }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <!-- 7) OBSERVACIONES GENERALES -->
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
            
                    // 🔥 usar un ejemplo REAL para medir
                    $sample = "Página 99 de 99";
                    $width = $fontMetrics->getTextWidth($sample, $font, $size);
            
                    $x = ($pdf->get_width() - $width) / 2 + 5;
            
                    // 🔥 ya ajustado para no encimarse con footer
                    $y = $pdf->get_height() - 45;
            
                    $pdf->page_text($x, $y, $text, $font, $size);
                }
            </script>
        </body>

        </html>
