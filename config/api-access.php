<?php

return [

  /*
    |--------------------------------------------------------------------------
    | Configuración de Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Define los límites de requests por minuto para diferentes roles y contextos.
    | Los límites se aplican por combinación de usuario, rol e IP.
    |
    */

  'rate_limits' => [

    // Límites por rol (requests por minuto)
    'by_role' => [
      'ADMINISTRADOR' => 1000,
      'GERENTE' => 500,
      'SUPERVISOR' => 300,
      'VENTAS' => 200,
      'AUXILIAR' => 100,
      'default' => 60,
      'guest' => 30
    ],

    // Límites especiales para endpoints específicos
    'by_endpoint' => [
      'auth/login' => [
        'limit' => 5,
        'window' => 60, // por minuto
        'by' => 'ip' // por IP, no por usuario
      ],
      'auth/register' => [
        'limit' => 3,
        'window' => 300, // 5 minutos
        'by' => 'ip'
      ],
      'proveedores/*/productos/import' => [
        'limit' => 10,
        'window' => 3600, // por hora
        'by' => 'user'
      ],
      'proveedores/*/users' => [
        'limit' => 50,
        'window' => 60,
        'by' => 'user'
      ]
    ],

    // Configuración de ventanas de tiempo
    'windows' => [
      'default' => 60, // segundos
      'extended' => 3600, // 1 hora para acciones pesadas
      'strict' => 300 // 5 minutos para acciones sensibles
    ]
  ],

  /*
    |--------------------------------------------------------------------------
    | Configuración de Endpoints Sensibles
    |--------------------------------------------------------------------------
    |
    | Define qué endpoints requieren validación especial y logging adicional.
    |
    */

  'sensitive_endpoints' => [

    // Patrones de rutas sensibles
    'patterns' => [
      'proveedores/*/users',
      'proveedores/*/productos/import',
      'auth/*',
      'admin/*',
      'usuarios/*/password',
      'proveedores/*/delete'
    ],

    // Configuración específica por patrón
    'configurations' => [
      'proveedores/*/users' => [
        'requires_main_user' => true,
        'log_all_actions' => true,
        'rate_limit_override' => 30
      ],
      'proveedores/*/productos/import' => [
        'requires_role' => ['ADMINISTRADOR', 'GERENTE', 'SUPERVISOR'],
        'log_all_actions' => true,
        'rate_limit_override' => 5,
        'max_file_size' => '10MB'
      ],
      'auth/*' => [
        'rate_limit_by_ip' => true,
        'log_failed_attempts' => true,
        'block_after_failures' => 10
      ],
      'admin/*' => [
        'requires_role' => ['ADMINISTRADOR'],
        'log_all_actions' => true,
        'rate_limit_override' => 100
      ]
    ]
  ],

  /*
    |--------------------------------------------------------------------------
    | Configuración de Auditoría y Logging
    |--------------------------------------------------------------------------
    |
    | Define qué acciones deben ser registradas y cómo.
    |
    */

  'audit' => [

    // Canales de log por tipo de acción
    'log_channels' => [
      'api_access' => 'daily',
      'security' => 'security',
      'audit' => 'audit',
      'performance' => 'performance'
    ],

    // Acciones que siempre deben ser auditadas
    'always_audit' => [
      'POST',
      'PUT',
      'PATCH',
      'DELETE'
    ],

    // Información a capturar en auditoría
    'capture_data' => [
      'request_headers' => true,
      'request_body' => true,
      'response_status' => true,
      'response_body_on_error' => true,
      'processing_time' => true,
      'memory_usage' => false,
      'database_queries' => false // Solo en debug
    ],

    // Campos sensibles que no deben ser registrados
    'sanitize_fields' => [
      'password',
      'password_confirmation',
      'current_password',
      'token',
      'api_token',
      'access_token',
      'refresh_token',
      'authorization',
      'cookie'
    ],

    // Retención de logs de auditoría
    'retention' => [
      'api_access' => 90, // días
      'security' => 365,
      'audit' => 2555, // 7 años
      'performance' => 30
    ]
  ],

  /*
    |--------------------------------------------------------------------------
    | Configuración de Bloqueo y Seguridad
    |--------------------------------------------------------------------------
    |
    | Define reglas para bloquear IPs o usuarios en caso de comportamiento sospechoso.
    |
    */

  'security' => [

    // Configuración de bloqueo por IP
    'ip_blocking' => [
      'enabled' => true,
      'max_failures' => 50,
      'failure_window' => 300, // 5 minutos
      'block_duration' => 3600, // 1 hora
      'whitelist' => [
        '127.0.0.1',
        '::1'
      ]
    ],

    // Configuración de bloqueo por usuario
    'user_blocking' => [
      'enabled' => true,
      'max_failures' => 20,
      'failure_window' => 300,
      'block_duration' => 1800, // 30 minutos
      'escalation' => [
        'second_block' => 3600, // 1 hora
        'third_block' => 7200   // 2 horas
      ]
    ],

    // Detección de patrones sospechosos
    'suspicious_patterns' => [
      'rapid_requests' => [
        'threshold' => 100, // requests
        'window' => 60,     // segundos
        'action' => 'log_and_slow'
      ],
      'failed_auth_pattern' => [
        'threshold' => 10,
        'window' => 300,
        'action' => 'block_ip'
      ],
      'endpoint_scanning' => [
        'threshold' => 20, // diferentes endpoints
        'window' => 60,
        'action' => 'log_and_monitor'
      ]
    ]
  ],

  /*
    |--------------------------------------------------------------------------
    | Configuración de Performance
    |--------------------------------------------------------------------------
    |
    | Configuraciones para optimizar el rendimiento del sistema de acceso.
    |
    */

  'performance' => [

    // Configuración de cache
    'cache' => [
      'enabled' => true,
      'driver' => 'redis', // redis, memcached, database
      'ttl' => [
        'user_permissions' => 300,    // 5 minutos
        'rate_limit_state' => 120,    // 2 minutos
        'ip_block_state' => 3600,     // 1 hora
        'endpoint_config' => 1800     // 30 minutos
      ],
      'prefix' => 'api_access:'
    ],

    // Configuración de métricas
    'metrics' => [
      'enabled' => true,
      'collect_response_times' => true,
      'collect_memory_usage' => false,
      'collect_query_counts' => false,
      'sample_rate' => 0.1 // 10% de requests
    ],

    // Optimizaciones
    'optimizations' => [
      'lazy_load_permissions' => true,
      'batch_audit_writes' => true,
      'async_logging' => true,
      'compress_audit_data' => true
    ]
  ],

  /*
    |--------------------------------------------------------------------------
    | Configuración de Notificaciones
    |--------------------------------------------------------------------------
    |
    | Define cuándo y cómo notificar sobre eventos de seguridad.
    |
    */

  'notifications' => [

    // Eventos que requieren notificación inmediata
    'immediate_alerts' => [
      'ip_blocked',
      'user_blocked',
      'suspicious_activity_detected',
      'multiple_failed_admin_access'
    ],

    // Canales de notificación
    'channels' => [
      'email' => [
        'enabled' => true,
        'recipients' => [
          'admin@company.com',
          'security@company.com'
        ]
      ],
      'slack' => [
        'enabled' => false,
        'webhook_url' => env('SLACK_SECURITY_WEBHOOK'),
        'channel' => '#security-alerts'
      ],
      'log' => [
        'enabled' => true,
        'level' => 'critical'
      ]
    ],

    // Configuración de resúmenes periódicos
    'digest' => [
      'enabled' => true,
      'frequency' => 'daily', // daily, weekly
      'time' => '08:00',
      'include' => [
        'top_users_by_requests',
        'top_endpoints_by_usage',
        'failed_auth_attempts',
        'blocked_ips_summary',
        'performance_metrics'
      ]
    ]
  ]
];
