<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Configuración de Alertas
    |--------------------------------------------------------------------------
    |
    | Configuración de thresholds y comportamiento de alertas para órdenes
    | de compra sin solicitudes de pago asociadas.
    |
    */

    'alertas' => [
        'thresholds' => [
            'warning' => env('OC_ALERTA_WARNING_DAYS', 7),   // Días para alerta amarilla
            'danger' => env('OC_ALERTA_DANGER_DAYS', 15),    // Días para alerta roja
        ],

        'colores' => [
            'warning' => '#ffc107',
            'danger' => '#dc3545',
            'success' => '#28a745',
            'info' => '#17a2b8',
        ],

        'cache' => [
            'ttl' => env('OC_ALERTAS_CACHE_TTL', 300), // 5 minutos en segundos
        ],

        'notificaciones' => [
            'enabled' => env('OC_NOTIFICACIONES_ENABLED', true),
            'canales' => ['database', 'mail'], // Canales disponibles
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Estados de Órdenes de Compra
    |--------------------------------------------------------------------------
    |
    | Configuración de estados y sus propiedades para órdenes de compra.
    |
    */

    'estados' => [
        'pendiente' => [
            'label' => 'Pendiente',
            'color' => '#ffc107',
            'puede_generar_sp' => false,
            'descripcion' => 'Orden de compra en espera de aprobación',
        ],
        'aprobada' => [
            'label' => 'Aprobada',
            'color' => '#28a745',
            'puede_generar_sp' => true,
            'descripcion' => 'Orden de compra aprobada, puede generar solicitudes de pago',
        ],
        'rechazada' => [
            'label' => 'Rechazada',
            'color' => '#dc3545',
            'puede_generar_sp' => false,
            'descripcion' => 'Orden de compra rechazada',
        ],
        'completada' => [
            'label' => 'Completada',
            'color' => '#6c757d',
            'puede_generar_sp' => false,
            'descripcion' => 'Orden de compra completamente convertida a solicitudes de pago',
        ],
        'parcial' => [
            'label' => 'Parcial',
            'color' => '#17a2b8',
            'puede_generar_sp' => true,
            'descripcion' => 'Orden de compra parcialmente convertida, aún tiene monto disponible',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Conversión OC → SP
    |--------------------------------------------------------------------------
    |
    | Configuraciones relacionadas con el proceso de conversión de órdenes
    | de compra a solicitudes de pago.
    |
    */

    'conversion' => [
        'permite_pagos_parciales' => env('OC_PERMITE_PAGOS_PARCIALES', true),
        'monto_minimo_sp' => env('OC_MONTO_MINIMO_SP', 0.01),
        'auto_update_estado' => env('OC_AUTO_UPDATE_ESTADO', true),
        'validar_fechas' => env('OC_VALIDAR_FECHAS', true),
        
        'mapeo_campos' => [
            'descripcion_auto' => true, // Auto-generar descripción si no se proporciona
            'heredar_empresa' => true,  // Heredar empresa de la OC
            'heredar_residente' => false, // No heredar residente por defecto
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Dashboard
    |--------------------------------------------------------------------------
    |
    | Configuraciones para el dashboard híbrido OC-SP.
    |
    */

    'dashboard' => [
        'items_por_pagina' => env('OC_DASHBOARD_PER_PAGE', 15),
        'mostrar_alertas' => env('OC_DASHBOARD_MOSTRAR_ALERTAS', true),
        'cache_estadisticas' => env('OC_DASHBOARD_CACHE_STATS', true),
        'cache_estadisticas_ttl' => env('OC_DASHBOARD_CACHE_STATS_TTL', 600), // 10 minutos
        
        'graficas' => [
            'mostrar_tendencias' => true,
            'periodo_default' => 30, // días
            'colores_graficas' => [
                'oc_pendientes' => '#ffc107',
                'oc_aprobadas' => '#28a745',
                'sp_generadas' => '#17a2b8',
                'montos' => '#6f42c1',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Integración con API Externa
    |--------------------------------------------------------------------------
    |
    | Configuraciones para la integración con APIs externas de órdenes de compra.
    |
    */

    'integracion' => [
        'api_externa' => [
            'url' => env('OC_API_EXTERNA_URL'),
            'timeout' => env('OC_API_EXTERNA_TIMEOUT', 30),
            'reintentos' => env('OC_API_EXTERNA_REINTENTOS', 3),
            'cache_ttl' => env('OC_API_EXTERNA_CACHE_TTL', 300), // 5 minutos
        ],

        'sincronizacion' => [
            'auto_sync' => env('OC_AUTO_SYNC', false),
            'intervalo_sync' => env('OC_SYNC_INTERVAL', 3600), // 1 hora en segundos
            'batch_size' => env('OC_SYNC_BATCH_SIZE', 50),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Validaciones y Reglas de Negocio
    |--------------------------------------------------------------------------
    |
    | Configuraciones para validaciones específicas del módulo OC.
    |
    */

    'validaciones' => [
        'numero_orden' => [
            'unico_por_proveedor' => true,
            'formato_regex' => null, // Regex opcional para validar formato
            'longitud_maxima' => 255,
        ],

        'montos' => [
            'permitir_decimales' => true,
            'decimales_precision' => 2,
            'monto_maximo' => env('OC_MONTO_MAXIMO', 999999999.99),
        ],

        'fechas' => [
            'fecha_orden_obligatoria' => true,
            'fecha_aprobacion_posterior' => true,
            'rango_fechas_maximo' => 365, // días
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Auditoría
    |--------------------------------------------------------------------------
    |
    | Configuraciones para el registro de auditoría de órdenes de compra.
    |
    */

    'auditoria' => [
        'enabled' => env('OC_AUDITORIA_ENABLED', true),
        'eventos_auditables' => [
            'creacion',
            'actualizacion', 
            'conversion_sp',
            'cambio_estado',
            'vinculacion',
            'desvinculacion',
        ],
        'retention_days' => env('OC_AUDITORIA_RETENTION_DAYS', 365),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Reportes
    |--------------------------------------------------------------------------
    |
    | Configuraciones para la generación de reportes.
    |
    */

    'reportes' => [
        'formatos_disponibles' => ['pdf', 'excel', 'csv'],
        'limite_registros' => env('OC_REPORTES_LIMITE', 10000),
        'cache_reportes' => env('OC_REPORTES_CACHE', true),
        'cache_reportes_ttl' => env('OC_REPORTES_CACHE_TTL', 1800), // 30 minutos
    ],

];