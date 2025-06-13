<?php

return [

  /*
    |--------------------------------------------------------------------------
    | Configuración de Permisos por Rol
    |--------------------------------------------------------------------------
    |
    | Define los permisos específicos para cada rol en el sistema de proveedores.
    | Los permisos se organizan por contexto (global, proveedor) y tipo de acción.
    |
    */

  'roles' => [

    'ADMINISTRADOR' => [
      'description' => 'Acceso total al sistema',
      'global_permissions' => [
        'view_all_proveedores',
        'create_proveedores',
        'update_any_proveedor',
        'delete_any_proveedor',
        'manage_system_users',
        'view_audit_logs',
        'manage_system_settings',
        'access_admin_panel'
      ],
      'proveedor_permissions' => [
        'all' // Acceso completo a cualquier proveedor
      ],
      'restrictions' => []
    ],

    'GERENTE' => [
      'description' => 'Gestión integral de proveedores y supervisión',
      'global_permissions' => [
        'view_assigned_proveedores',
        'create_proveedor_users',
        'view_proveedor_reports'
      ],
      'proveedor_permissions' => [
        'view_proveedor_data',
        'update_proveedor_info',
        'manage_proveedor_users',
        'manage_proveedor_productos',
        'import_productos',
        'export_productos',
        'view_sensitive_data',
        'manage_proveedor_settings'
      ],
      'restrictions' => [
        'only_assigned_proveedores' => true,
        'cannot_delete_proveedor' => true
      ]
    ],

    'SUPERVISOR' => [
      'description' => 'Supervisión de operaciones y control parcial',
      'global_permissions' => [
        'view_assigned_proveedores'
      ],
      'proveedor_permissions' => [
        'view_proveedor_data',
        'update_productos',
        'create_productos',
        'import_productos',
        'view_proveedor_users',
        'view_basic_reports'
      ],
      'restrictions' => [
        'only_assigned_proveedores' => true,
        'cannot_manage_users' => true,
        'cannot_view_sensitive_data' => true,
        'cannot_delete_productos' => false
      ]
    ],

    'VENTAS' => [
      'description' => 'Gestión de ventas y catálogos de productos',
      'global_permissions' => [
        'view_assigned_proveedores'
      ],
      'proveedor_permissions' => [
        'view_proveedor_data',
        'view_productos',
        'update_productos',
        'create_productos',
        'view_product_reports',
        'export_productos'
      ],
      'restrictions' => [
        'only_assigned_proveedores' => true,
        'cannot_manage_users' => true,
        'cannot_view_sensitive_data' => true,
        'cannot_delete_productos' => true,
        'cannot_import_productos' => true
      ]
    ],

    'AUXILIAR' => [
      'description' => 'Permisos limitados para tareas específicas',
      'global_permissions' => [
        'view_assigned_proveedores'
      ],
      'proveedor_permissions' => [
        'view_proveedor_data',
        'view_productos',
        'update_producto_stock',
        'create_productos'
      ],
      'restrictions' => [
        'only_assigned_proveedores' => true,
        'cannot_manage_users' => true,
        'cannot_view_sensitive_data' => true,
        'cannot_delete_anything' => true,
        'cannot_import_productos' => true,
        'read_only_proveedor_info' => true
      ]
    ]
  ],

  /*
    |--------------------------------------------------------------------------
    | Mapeo de Acciones a Permisos
    |--------------------------------------------------------------------------
    |
    | Define qué permisos se requieren para cada acción específica en el sistema.
    |
    */

  'action_permissions' => [

    // Gestión de Proveedores
    'proveedor.view' => ['view_proveedor_data'],
    'proveedor.create' => ['create_proveedores'],
    'proveedor.update' => ['update_proveedor_info', 'update_any_proveedor'],
    'proveedor.delete' => ['delete_any_proveedor'],
    'proveedor.view_sensitive' => ['view_sensitive_data'],

    // Gestión de Usuarios de Proveedor
    'proveedor.users.view' => ['view_proveedor_users', 'manage_proveedor_users'],
    'proveedor.users.create' => ['manage_proveedor_users', 'create_proveedor_users'],
    'proveedor.users.update' => ['manage_proveedor_users'],
    'proveedor.users.delete' => ['manage_proveedor_users'],

    // Gestión de Productos
    'productos.view' => ['view_productos'],
    'productos.create' => ['create_productos'],
    'productos.update' => ['update_productos'],
    'productos.delete' => ['delete_productos'],
    'productos.import' => ['import_productos'],
    'productos.export' => ['export_productos'],

    // Reportes y Análisis
    'reports.basic' => ['view_basic_reports'],
    'reports.advanced' => ['view_proveedor_reports'],
    'reports.sensitive' => ['view_sensitive_data'],

    // Administración del Sistema
    'admin.access' => ['access_admin_panel'],
    'admin.users' => ['manage_system_users'],
    'admin.settings' => ['manage_system_settings'],
    'admin.audit' => ['view_audit_logs']
  ],

  /*
    |--------------------------------------------------------------------------
    | Configuración de Validación de Acceso
    |--------------------------------------------------------------------------
    |
    | Configuraciones para la validación de acceso a recursos.
    |
    */

  'access_validation' => [

    // Tiempo de cache para permisos (en segundos)
    'cache_ttl' => 300,

    // Campos sensibles que requieren permisos especiales
    'sensitive_fields' => [
      'rfc',
      'direccion_fiscal',
      'contacto_correo',
      'contacto_telefono',
      'validado_por',
      'notas'
    ],

    // Acciones que requieren ser usuario principal
    'main_user_only_actions' => [
      'proveedor.users.create',
      'proveedor.users.delete',
      'proveedor.delete',
      'proveedor.update_sensitive'
    ],

    // Acciones que requieren logging especial
    'high_impact_actions' => [
      'proveedor.users.create',
      'proveedor.users.delete',
      'productos.import',
      'proveedor.delete',
      'productos.bulk_update'
    ]
  ],

  /*
    |--------------------------------------------------------------------------
    | Configuración de Contexto de Proveedor
    |--------------------------------------------------------------------------
    |
    | Define cómo se maneja el contexto de proveedor en diferentes situaciones.
    |
    */

  'proveedor_context' => [

    // Métodos para extraer el ID del proveedor del request
    'extraction_methods' => [
      'route_parameter', // /proveedores/{proveedor}
      'query_parameter', // ?proveedor_id=123
      'request_body',    // {"proveedor_id": 123}
      'url_segment'      // /api/proveedores/123/productos
    ],

    // Nombres de parámetros válidos para el proveedor
    'parameter_names' => [
      'proveedor',
      'proveedor_id',
      'supplier',
      'supplier_id'
    ],

    // Rutas que no requieren contexto de proveedor
    'context_free_routes' => [
      'auth/*',
      'admin/*',
      'health-check',
      'api/tipos-empresa-index',
      'api/categorias-index'
    ]
  ],

  /*
    |--------------------------------------------------------------------------
    | Mensajes de Error Personalizados
    |--------------------------------------------------------------------------
    |
    | Mensajes de error específicos para diferentes situaciones de permisos.
    |
    */

  'error_messages' => [
    'no_proveedor_access' => 'No tienes permisos para acceder a los recursos de este proveedor.',
    'insufficient_role' => 'Tu rol no tiene permisos suficientes para realizar esta acción.',
    'main_user_required' => 'Esta acción solo puede ser realizada por el usuario principal del proveedor.',
    'inactive_relation' => 'Tu relación con este proveedor está inactiva. Contacta al administrador.',
    'context_not_found' => 'No se pudo determinar el contexto del proveedor para esta solicitud.',
    'sensitive_data_denied' => 'No tienes permisos para acceder a datos sensibles de este proveedor.'
  ]
];
