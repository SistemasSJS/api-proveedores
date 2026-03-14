@php
    $margenMm = 18;
    $condicionesDefault = [
        'tiempo_entrega' => '3 días hábiles a partir de anticipo',
        'condiciones_pago' => '50% anticipo, 50% contra entrega',
        'garantia' => '30 días por mano de obra, no incluye mal uso',
        'vigencia' => '7 días naturales',
    ];
    $cond = $presupuesto['condiciones'] ?? [];

    // Construir lista de TÉRMINOS Y CONDICIONES (solo las que existen)
    $terminosLista = [];

    if (!empty($cond['vigencia_activo']) && !empty($cond['vigencia_dias'])) {
        $terminosLista[] = [
            'titulo' => 'Vigencia del presupuesto',
            'texto' =>
                'Este presupuesto tiene una vigencia de ' .
                (int) $cond['vigencia_dias'] .
                ' días naturales a partir de su fecha de emisión.',
        ];
    } elseif (!empty($cond['vigencia'])) {
        $terminosLista[] = ['titulo' => 'Vigencia del presupuesto', 'texto' => $cond['vigencia']];
    }

    if (!empty($cond['moneda_activo'])) {
        $terminosLista[] = [
            'titulo' => 'Moneda',
            'texto' => 'Los precios están expresados en moneda nacional (MXN), salvo que se indique lo contrario.',
        ];
    }

    $conIvaPdf = $presupuesto['con_iva'] ?? false;
    $ivaPct = $presupuesto['iva_porcentaje'] ?? 16;
    if (!empty($cond['impuestos_activo'])) {
        $terminosLista[] = [
            'titulo' => 'Impuestos',
            'texto' => $conIvaPdf
                ? 'Los precios incluyen el Impuesto al Valor Agregado (IVA) al ' . (int) $ivaPct . '%.'
                : 'Los precios no incluyen el Impuesto al Valor Agregado (IVA).',
        ];
    }

    if (!empty($cond['anticipo_activo']) && isset($cond['anticipo_porcentaje'])) {
        $terminosLista[] = [
            'titulo' => 'Anticipo',
            'texto' =>
                'Para iniciar los trabajos se requiere un anticipo del ' .
                (int) $cond['anticipo_porcentaje'] .
                '% del monto total.',
        ];
    }

    if (!empty($cond['entrega_activo']) && !empty($cond['entrega_tipo'])) {
        $entregaTexto =
            ($cond['entrega_tipo'] ?? '') === 'despues'
                ? 'Una vez entregados los trabajos o productos se deberá cubrir el 100% del monto total del presupuesto.'
                : 'Para la entrega de los trabajos o productos se deberá haber cubierto el 100% del monto total del presupuesto.';
        $terminosLista[] = ['titulo' => 'Entrega de trabajos o productos', 'texto' => $entregaTexto];
    }

    if (!empty($cond['tiempo_entrega_activo']) && !empty($cond['tiempo_entrega_dias'])) {
        $terminosLista[] = [
            'titulo' => 'Tiempo de entrega o ejecución',
            'texto' =>
                'Una vez recibido el anticipo, el tiempo estimado de entrega o ejecución total de los trabajos será de ' .
                (int) $cond['tiempo_entrega_dias'] .
                ' días naturales.',
        ];
    } elseif (!empty($cond['tiempo_entrega'])) {
        $terminosLista[] = ['titulo' => 'Tiempo de entrega', 'texto' => $cond['tiempo_entrega']];
    }

    if (!empty($cond['disponibilidad_materiales_activo'])) {
        $terminosLista[] = [
            'titulo' => 'Disponibilidad de materiales o refacciones',
            'texto' =>
                'Los tiempos de entrega o ejecución pueden variar dependiendo de la disponibilidad de materiales, refacciones o insumos necesarios.',
        ];
    }

    if (!empty($cond['trabajos_adicionales_activo'])) {
        $terminosLista[] = [
            'titulo' => 'Trabajos o conceptos adicionales',
            'texto' => 'Cualquier trabajo o concepto no incluido en este presupuesto será cotizado por separado.',
        ];
    }

    if (!empty($cond['alcance_activo'])) {
        $terminosLista[] = [
            'titulo' => 'Alcance del presupuesto',
            'texto' => 'Este presupuesto incluye únicamente los trabajos o productos descritos en este documento.',
        ];
    }

    if (!empty($cond['cancelacion_activo'])) {
        $terminosLista[] = [
            'titulo' => 'Cancelación del pedido o servicio',
            'texto' =>
                'En caso de cancelación del servicio o pedido una vez autorizado el presupuesto, los gastos o trabajos ya realizados deberán ser cubiertos por el cliente.',
        ];
    }

    if (!empty($cond['autorizacion_gestionpro_activo'])) {
        $terminosLista[] = [
            'titulo' => 'Autorización mediante GestiónPro',
            'texto' =>
                'La autorización de este presupuesto mediante la aplicación GestiónPro implica la confirmación del cliente para el inicio de los trabajos o suministros descritos.',
        ];
    }

    foreach (
        [
            'condicionantes_adicionales_1',
            'condicionantes_adicionales_2',
            'condicionantes_adicionales_3',
            'condicionantes_adicionales_4',
        ]
        as $key
    ) {
        if (!empty(trim($cond[$key] ?? ''))) {
            $terminosLista[] = ['titulo' => '', 'texto' => trim($cond[$key])];
        }
    }

    if (empty($terminosLista)) {
        $tiempoEntrega = $cond['tiempo_entrega'] ?? $condicionesDefault['tiempo_entrega'];
        $condicionesPago = $cond['condiciones_pago'] ?? $condicionesDefault['condiciones_pago'];
        $garantia = $cond['garantia'] ?? $condicionesDefault['garantia'];
        $vigencia = $cond['vigencia'] ?? $condicionesDefault['vigencia'];
        $terminosLista = [
            ['titulo' => 'Tiempo de entrega', 'texto' => $tiempoEntrega],
            ['titulo' => 'Condiciones de pago', 'texto' => $condicionesPago],
            ['titulo' => 'Garantía', 'texto' => $garantia],
            ['titulo' => 'Vigencia del presupuesto', 'texto' => $vigencia],
            ['titulo' => 'Moneda', 'texto' => 'Los precios están expresados en moneda nacional (MXN).'],
        ];
    }

    // Construir lista de OBSERVACIONES GENERALES
    $observacionesLista = [];

    if (!empty($cond['garantia_activo']) && !empty($cond['garantia_dias'])) {
        $observacionesLista[] =
            'La garantía de los trabajos o productos tendrá una vigencia de ' .
            (int) $cond['garantia_dias'] .
            ' días a partir de la finalización de los trabajos o entrega de los productos.';
    } elseif (!empty($cond['garantia'])) {
        $observacionesLista[] = 'Garantía: ' . $cond['garantia'];
    }

    if (!empty($cond['gastos_traslado_activo']) && isset($cond['gastos_traslado'])) {
        $incluidos = ($cond['gastos_traslado'] ?? '') === 'incluidos';
        $observacionesLista[] =
            'Los trabajos contemplados en este presupuesto ' .
            ($incluidos ? 'sí' : 'no') .
            ' incluyen los gastos de traslado al sitio donde se realizarán los trabajos.';
    }

    if (!empty($cond['viaticos_activo']) && isset($cond['viaticos'])) {
        $incluidos = ($cond['viaticos'] ?? '') === 'incluidos';
        $observacionesLista[] =
            'Los trabajos contemplados en este presupuesto ' .
            ($incluidos ? 'sí' : 'no') .
            ' incluyen los gastos de viáticos derivados de la ubicación donde deberán realizarse los trabajos.';
    }

    if (!empty($cond['revision_tecnica_activo'])) {
        $observacionesLista[] = 'El diagnóstico o alcance final podrá ajustarse una vez realizada la revisión técnica.';
    }

    if (!empty($cond['condiciones_sitio_activo'])) {
        $observacionesLista[] =
            'El cliente deberá proporcionar acceso y condiciones adecuadas para la ejecución de los trabajos.';
    }

    foreach (
        [
            'observaciones_adicionales_1',
            'observaciones_adicionales_2',
            'observaciones_adicionales_3',
            'observaciones_adicionales_4',
        ]
        as $key
    ) {
        if (!empty(trim($cond[$key] ?? ''))) {
            $observacionesLista[] = trim($cond[$key]);
        }
    }

    if (!empty(trim($presupuesto['observaciones'] ?? ''))) {
        $observacionesLista[] = trim($presupuesto['observaciones']);
    }

    if (!empty($presupuesto['proveedor']->datos_bancarios ?? null)) {
        $bancarios = is_string($presupuesto['proveedor']->datos_bancarios)
            ? json_decode($presupuesto['proveedor']->datos_bancarios, true)
            : $presupuesto['proveedor']->datos_bancarios;
        if ($bancarios) {
            $observacionesLista[] =
                'Datos bancarios: Banco ' .
                ($bancarios['banco'] ?? 'N/A') .
                ', CLABE ' .
                ($bancarios['clabe_interbancaria'] ?? 'N/A') .
                ', Cuenta ' .
                ($bancarios['numero_cuenta'] ?? 'N/A') .
                '.';
        }
    }
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
            display: flex;
            flex-direction: column;
            min-height: 250mm;
        }

        .document-main {
            flex: 0 0 auto;
        }

        .terms-spacer {
            flex: 1 1 auto;
            min-height: 8mm;
        }

        .terms-block {
            flex: 0 0 auto;
            margin-top: 0;
            margin-bottom: 0;
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
            margin-bottom: 0;
            margin-top: 0;
            padding: 4mm 6mm;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 2mm;
            page-break-inside: avoid;
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

        .terminos-parrafo {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 7pt;
            color: #374151;
            line-height: 1.5;
            text-align: justify;
        }

        .terminos-parrafo strong {
            font-weight: 700;
            color: #2c3e50;
        }

        /* ========== 7) OBSERVACIONES GENERALES ========== */
        .observaciones-section {
            margin-bottom: 0;
            margin-top: 3mm;
            padding: 4mm 6mm;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 2mm;
            page-break-inside: avoid;
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

        .observaciones-parrafo {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 7pt;
            color: #374151;
            line-height: 1.5;
            text-align: justify;
        }

        /* ========== 8) PIE DE PÁGINA (estilo cotizaciones/facturas) ========== */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            min-height: 22mm;
            padding: 3mm {{ $margenMm }}mm;
            border-top: 1px solid #e5e7eb;
            background: #f3f4f6;
            font-size: 5.5pt;
            color: #6b7280;
            line-height: 1.3;
            display: table;
            width: 100%;
        }

        .footer-left {
            display: table-cell;
            width: 40%;
            vertical-align: middle;
        }

        .footer-center {
            display: table-cell;
            width: 20%;
            text-align: center;
            vertical-align: middle;
        }

        .footer-right {
            display: table-cell;
            width: 40%;
            text-align: right;
            vertical-align: middle;
        }

        .footer-titulo {
            font-size: 6pt;
            font-weight: 700;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
            margin-bottom: 2mm;
        }

        .footer-logos {
            display: table;
            width: 100%;
        }

        .footer-logo-item {
            display: table-cell;
            width: 33%;
            text-align: center;
            vertical-align: top;
            padding-right: 2mm;
        }

        .footer-logo-item:last-child {
            padding-right: 0;
        }

        .footer-logo-link {
            display: block;
            color: #6b7280;
            text-decoration: none;
        }

        .footer-logo-link img {
            width: 10mm;
            height: 10mm;
            object-fit: contain;
            display: block;
            margin: 0 auto 1mm;
        }

        .footer-logo-placeholder {
            width: 10mm;
            height: 10mm;
            display: block;
            margin: 0 auto 1mm;
            background: #e5e7eb;
            border-radius: 2mm;
            font-size: 6pt;
            font-weight: 700;
            color: #6b7280;
            text-align: center;
            line-height: 10mm;
        }

        .footer-logo-name {
            font-weight: 600;
            color: #4b5563;
            font-size: 5.5pt;
        }

        .footer-logo-url {
            font-size: 4.5pt;
            color: #9ca3af;
            word-break: break-all;
        }

        .footer-pages {
            font-weight: 600;
            color: #374151;
            font-size: 6pt;
        }

        .footer-qr {
            display: inline-block;
            width: 24mm;
            height: 24mm;
        }

        .footer-qr a {
            display: block;
            width: 100%;
            height: 100%;
        }

        .footer-qr img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
    </style>
</head>

<body>
    {{-- Footer primero para que DomPDF lo repita en todas las páginas --}}
    <div class="footer">
        <div class="footer-left">
            @php
                $logos = $presupuesto['logos_base64'] ?? [];
                $appFooter = [
                    ['key' => 'facturapro', 'name' => 'FacturaPro', 'url' => 'https://facturaspro.com.mx'],
                    ['key' => 'constucc', 'name' => 'Construcc', 'url' => 'https://construcc.mx'],
                    ['key' => 'gestionpro', 'name' => 'GestiónPro', 'url' => 'https://gestion.heventec.com'],
                ];
            @endphp
            <div class="footer-titulo">Desarrollado con nuestras aplicaciones</div>
            <div class="footer-logos">
                @foreach ($appFooter as $app)
                    <div class="footer-logo-item">
                        <a href="{{ $app['url'] }}" class="footer-logo-link" target="_blank">
                            @if(!empty($logos[$app['key']]))
                                <img src="{{ $logos[$app['key']] }}" alt="{{ $app['name'] }}" />
                            @else
                                <span class="footer-logo-placeholder">{{ substr($app['name'], 0, 1) }}</span>
                            @endif
                            <span class="footer-logo-name">{{ $app['name'] }}</span>
                            <span class="footer-logo-url">{{ $app['url'] }}</span>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="footer-center">
            <span class="footer-pages">&nbsp;</span>
        </div>
        <div class="footer-right">
            @if (isset($presupuesto['qr_code']) && $presupuesto['qr_code'])
                <div class="footer-qr">
                    @if (!empty($presupuesto['qr_url']))
                        <a href="{{ $presupuesto['qr_url'] }}" target="_blank" rel="noopener" title="Ver versión web">
                            <img src="{{ $presupuesto['qr_code'] }}" alt="Ver versión web" />
                        </a>
                    @else
                        <img src="{{ $presupuesto['qr_code'] }}" alt="Ver versión web" title="Ver versión web" />
                    @endif
                </div>
            @endif
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
                                    <div class="company-header-info">RFC: {{ $emisorRfc }}</div>
                                @endif
                                @if ($emisorDireccion)
                                    <div class="company-header-info">{{ $emisorDireccion }}</div>
                                @endif
                                @if ($emisorCiudad)
                                    <div class="company-header-info">{{ $emisorCiudad }}</div>
                                @endif
                                @if ($emisorTel)
                                    <div class="company-header-info">Tel: {{ $emisorTel }}</div>
                                @endif
                                @if ($emisorEmail)
                                    <div class="company-header-info">Email: {{ $emisorEmail }}</div>
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
                        $empresa = $presupuesto['empresa_receptora']['empresa'] ?? null;
                        $nombre = $presupuesto['empresa_receptora']['nombre'] ?? null;
                    @endphp
                    @if ($empresa)
                        <div class="receptor-name">{{ $empresa }}</div>
                    @elseif($nombre)
                        <div class="receptor-name">{{ $nombre }}</div>
                    @endif

                    @if ($nombre)
                        <div class="receptor-info"><strong>Nombre:</strong> {{ $nombre }}</div>
                    @endif

                    @if ($presupuesto['empresa_receptora']['puesto'] ?? null)
                        <div class="receptor-info"><strong>Cargo o puesto:</strong>
                            {{ $presupuesto['empresa_receptora']['puesto'] }}</div>
                    @endif

                    @if ($presupuesto['empresa_receptora']['correo'] ?? null)
                        <div class="receptor-info"><strong>Email:</strong>
                            {{ $presupuesto['empresa_receptora']['correo'] }}</div>
                    @endif

                    @if ($presupuesto['empresa_receptora']['telefono'] ?? null)
                        <div class="receptor-info"><strong>Teléfono:</strong>
                            {{ $presupuesto['empresa_receptora']['telefono'] }}</div>
                    @endif

                    @if ($presupuesto['empresa_receptora']['direccion'] ?? null)
                        <div class="receptor-info"><strong>Dirección:</strong>
                            {{ $presupuesto['empresa_receptora']['direccion'] }}</div>
                    @endif
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
            <div class="terms-spacer"></div>
            <div class="terms-block">
                <!-- 6) TÉRMINOS Y CONDICIONES -->
                @if (count($terminosLista) > 0)
                    <div class="terminos-section">
                        <div class="terminos-main-title">Términos y Condiciones</div>
                        <div class="terminos-parrafo">
                            @foreach ($terminosLista as $idx => $item)
                                {{ $item['texto'] }}@if(!$loop->last). @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- 7) OBSERVACIONES GENERALES -->
                @if (count($observacionesLista) > 0)
                    <div class="observaciones-section">
                        <div class="observaciones-title">Observaciones Generales</div>
                        <div class="observaciones-parrafo">
                            @foreach ($observacionesLista as $obs)
                                {{ $obs }}@if(!$loop->last). @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="margin-bottom"></div>

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
