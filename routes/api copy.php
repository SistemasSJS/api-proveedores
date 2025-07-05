<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\ProveedorPedidoController;
use App\Enums\UserRoleEnumerate;

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminHomeControler;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ClienteDashboardController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ImagenController;
use App\Http\Controllers\UnidadMedidaController;
use App\Http\Controllers\LineaController;
use App\Http\Controllers\MarcaController;
use App\Http\Controllers\ProveedorProductoController;
use App\Http\Controllers\ProductoImagenController;
use App\Http\Controllers\ProductoImportController;
use App\Http\Controllers\ProveedorCategoriaController;
use App\Http\Controllers\ProveedorLineaController;
use App\Http\Controllers\ProveedorMarcaController;
use App\Http\Controllers\ProveedorTipoEmpresaController;
use App\Http\Controllers\ProveedorUsuarioController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TipoEmpresaController;

use App\Http\Controllers\ProveedorSucursalController;
use App\Http\Controllers\SucursalProductoController;
use App\Http\Controllers\RequisicionController;
use App\Http\Controllers\ProveedorRequisicionController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProveedorDashboardController;
use App\Http\Controllers\ProductoBusquedaController;
use App\Http\Controllers\ProveedorReporteController;
use App\Http\Controllers\TiendaController;
use App\Http\Middleware\EnsureProveedorOwnership;
use App\Http\Middleware\ValidateApiAccess;
use App\Http\Middleware\LogApiActions;

/**
 * RUTAS DE AUTENTICACION Y REGISTRO DE USUARIOS
 */
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->middleware(['audit']); // LogApiActions alias es 'audit'
    Route::post('register', [AuthController::class, 'register']);
    Route::post('completar-registro', [AuthController::class, 'register_completar']);
    Route::post('register_proveedor', [AuthController::class, 'register_proveedor']);
    Route::post('register_proveedor_completar', [AuthController::class, 'register_proveedor_completar']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('update-img-perfil', [AuthController::class, 'update_foto_perfil']);
        Route::get('logout', [AuthController::class, 'logout']);
    });
});

/**
 * Rutas públicas de listados de catálogos
 */
Route::get('roles-index', [RoleController::class, 'index']);
// Route::get('categorias-index', [CategoriaController::class, 'index']);
// Route::get('subcategorias-index', [CategoriaController::class, 'index']);
// Route::get('lineas-index', [LineaController::class, 'index']);
// Route::get('marcas-index', [MarcaController::class, 'index']);
// Route::get('unidades-medida-index', [UnidadMedidaController::class, 'index']);
Route::get('tipos-empresa-index', [TipoEmpresaController::class, 'index']);


/**
 * Rutas públicas de catálogos: Productos, Proveedores, Sucursales, marcas, líneas, categorías, unidades de medida
 */
// Route::get('/', [ProveedorSucursalController::class, 'index'])->middleware(['audit']);
Route::get('proveedores', [ProveedorController::class, 'index'])->middleware(['audit']);
Route::get('sucursales', [SucursalController::class, 'index'])->middleware(['audit']);
Route::get('productos', [ProductoController::class, 'index'])->middleware(['audit']);
Route::get('imagenes', [ProductoImagenController::class, 'index'])->middleware(['audit']);
Route::get('unidades-medida', [UnidadMedidaController::class, 'index'])->middleware(['audit']);
Route::get('categorias', [CategoriaController::class, 'index'])->middleware(['audit']);
Route::get('lineas', [LineaController::class, 'index'])->middleware(['audit']);
Route::get('marcas', [MarcaController::class, 'index'])->middleware(['audit']);
Route::get('tipos-empresa', [TipoEmpresaController::class, 'index'])->middleware(['audit']);



/**
 * Rutas protegidas con auth:sanctum
 */
Route::middleware('auth:sanctum')->group(function () {

    /**
     * RUTAS DE LA TIENDA ONLINE
     */
    Route::prefix('tienda')->group(function () {
        Route::get('accesos-rapidos', [TiendaController::class, 'accesosRapidos'])->middleware(['audit']);

        Route::prefix('proveedores')->group(function () {
            Route::get('principales', [TiendaController::class, 'proveedoresPrincipales'])->middleware(['audit']);
        });

        Route::prefix('productos')->group(function () {
            Route::get('destacados', [TiendaController::class, 'productosDestacados'])->middleware(['audit']);
            Route::get('mas-pedidos', [TiendaController::class, 'productosMasPedidos'])->middleware(['audit']);
            Route::get('recientes', [TiendaController::class, 'productosRecientes'])->middleware(['audit']);
        });
    });

    /**
     * Rutas con roles GERENTE y ADMINISTRADOR
     */
    Route::middleware('role:' . UserRoleEnumerate::CLIENTE->value . ',' . UserRoleEnumerate::GERENTE->value . ',' . UserRoleEnumerate::ADMINISTRADOR->value)->group(function () {


        /**
         * Gestión de proveedores
         */
        Route::prefix('proveedores')->group(function () {



            // Rutas CRUD y perfil para proveedores
            Route::controller(ProveedorController::class)->group(function () {
                // Route::get('/', 'index')->middleware(['audit']);
                Route::post('/', 'store')->middleware(['audit']);
                Route::get('{proveedor}', 'show')->middleware(['api.access', 'audit']);
                Route::patch('{proveedor}', 'update')->middleware(['api.access', 'audit']);
                Route::delete('{proveedor}', 'destroy')->middleware(['api.access', 'audit']);
                Route::post('{proveedor}/logo', 'updateLogo')->middleware(['api.access', 'audit']);
            });

            // Importaciones de productos
            Route::prefix('{proveedor}/imports')->middleware(['proveedor.access'])->group(function () {
                Route::post('products', [ProductoImportController::class, 'upload']);
                Route::get('products', [ProductoImportController::class, 'list']);
                Route::get('{audit}', [ProductoImportController::class, 'status']);
                Route::get('{audit}/logs', [ProductoImportController::class, 'status']);
                Route::post('{audit}/confirm', [ProductoImportController::class, 'confirm']);
            });
            Route::get('/imports/products/template', [ProductoImportController::class, 'downloadTemplate']);

            /**
             * Gestión de usuarios del proveedor
             */
            Route::prefix('{proveedor}/users')->middleware(['proveedor.access'])->group(function () {
                Route::get('/', [ProveedorUsuarioController::class, 'index'])->middleware(['api.access']);
                Route::post('/', [ProveedorUsuarioController::class, 'store'])->middleware(['api.access', 'audit']);
                Route::middleware(['api.access', 'proveedor.user', 'audit'])->group(function () {
                    Route::get('{user}', [ProveedorUsuarioController::class, 'show']);
                    Route::patch('{user}', [ProveedorUsuarioController::class, 'update']);
                    Route::delete('{user}', [ProveedorUsuarioController::class, 'destroy']);
                    Route::post('{user}/logo', [ProveedorUsuarioController::class, 'updateLogo']);
                });
            });

            /**
             * Gestión de productos del proveedor
             */
            Route::prefix('{proveedor}/productos')->middleware(['proveedor.access'])->group(function () {
                Route::get('/', [ProveedorProductoController::class, 'index'])->middleware(['audit']);
                Route::post('/', [ProveedorProductoController::class, 'store'])->middleware(['audit']);
                Route::middleware(['proveedor.producto', 'audit'])->group(function () {
                    Route::get('{producto}', [ProveedorProductoController::class, 'show']);
                    Route::patch('{producto}', [ProveedorProductoController::class, 'update']);
                    Route::delete('{producto}', [ProveedorProductoController::class, 'destroy']);
                    Route::post('{producto}/logo', [ProveedorProductoController::class, 'updateLogo']);
                });
            });

            /**
             * Gestión de categorías del proveedor
             */
            Route::prefix('{proveedor}/categorias')->middleware(['proveedor.access'])->group(function () {
                Route::get('/', [ProveedorCategoriaController::class, 'index'])->middleware(['audit']);
                Route::post('/', [ProveedorCategoriaController::class, 'store'])->middleware(['audit']);
                Route::middleware(['proveedor.categoria', 'audit'])->group(function () {
                    Route::get('{categoria}', [ProveedorCategoriaController::class, 'show']);
                    Route::patch('{categoria}', [ProveedorCategoriaController::class, 'update']);
                    Route::delete('{categoria}', [ProveedorCategoriaController::class, 'destroy']);
                    Route::post('{categoria}/logo', [ProveedorCategoriaController::class, 'updateLogo']);
                    Route::get('{categoria}/subcategorias', [ProveedorCategoriaController::class, 'index_sub_categorias'])->middleware(['audit']);
                });
            });

            /**
             * Gestión de 1 del proveedor
             */
            Route::prefix('{proveedor}/marcas')->middleware(['proveedor.access'])->group(function () {
                Route::get('/', [ProveedorMarcaController::class, 'index'])->middleware(['audit']);
                Route::post('/', [ProveedorMarcaController::class, 'store'])->middleware(['audit']);
                Route::middleware(['proveedor.marca', 'audit'])->group(function () {
                    Route::get('{marca}', [ProveedorMarcaController::class, 'show']);
                    Route::patch('{marca}', [ProveedorMarcaController::class, 'update']);
                    Route::delete('{marca}', [ProveedorMarcaController::class, 'destroy']);
                    Route::post('{marca}/logo', [ProveedorMarcaController::class, 'updateLogo']);
                    Route::get('/{marca}/lineas', [ProveedorMarcaController::class, 'index_lineas_por_marca'])->middleware(['audit']);
                });
            });

            /**
             * Gestión de líneas del proveedor
             */
            Route::prefix('{proveedor}/lineas')->middleware(['proveedor.access'])->group(function () {
                Route::get('/', [ProveedorLineaController::class, 'index'])->middleware(['audit']);
                Route::post('/', [ProveedorLineaController::class, 'store'])->middleware(['audit']);
                // Opcional: Rutas para show, update, delete
                Route::middleware(['proveedor.linea', 'audit'])->group(function () {
                    Route::get('{linea}', [ProveedorLineaController::class, 'show']);
                    Route::patch('{linea}', [ProveedorLineaController::class, 'update']);
                    Route::delete('{linea}', [ProveedorLineaController::class, 'destroy']);
                });
            });

            /**
             * Gestión de sucursales del proveedor
             */
            Route::prefix('{proveedor}/sucursales')->middleware(['proveedor.access'])->group(function () {
                Route::get('/', [ProveedorSucursalController::class, 'index'])->middleware(['audit']);
                Route::post('/', [ProveedorSucursalController::class, 'store'])->middleware(['audit']);
                Route::middleware(['proveedor.sucursal', 'audit'])->group(function () {
                    Route::get('{sucursal}', [ProveedorSucursalController::class, 'show']);
                    Route::patch('{sucursal}', [ProveedorSucursalController::class, 'update']);
                    Route::delete('{sucursal}', [ProveedorSucursalController::class, 'destroy']);
                });
            });

            /**
             * Gestión de productos por sucursal
             */
            Route::prefix('{proveedor}/sucursales/{sucursal}/productos')->middleware(['proveedor.access'])->group(function () {
                Route::get('/', [SucursalProductoController::class, 'index'])->middleware(['audit']);
                Route::post('asignar', [SucursalProductoController::class, 'asignarProductos'])->middleware(['audit']);
                Route::delete('desasignar', [SucursalProductoController::class, 'desasignarProductos'])->middleware(['audit']);
                Route::patch('{producto}', [SucursalProductoController::class, 'updateStock'])->middleware(['audit']);
            });

            /**
             * Gestión de requisiciones del proveedor
             */
            Route::prefix('{proveedor}/requisiciones')->middleware(['proveedor.access'])->group(function () {
                Route::get('/', [ProveedorRequisicionController::class, 'index'])->middleware(['audit']);
                Route::get('{requisicion}', [ProveedorRequisicionController::class, 'show'])->middleware(['audit']);
                Route::patch('{requisicion}/estatus', [ProveedorRequisicionController::class, 'cambiarEstatus'])->middleware(['audit']);
                Route::post('{requisicion}/cotizar', [ProveedorRequisicionController::class, 'generarCotizacion'])->middleware(['audit']);
            });

            /**
             * Recursos select (comentados en el código original para futura implementación)
             * - roles-index: /api/proveedores/{proveedor_id}/roles
             * - categorias-index: /api/proveedores/{proveedor_id}/categorias
             * - subcategorias-index: /api/proveedores/{proveedor_id}/categorias/{categoria_id}/subcategorias
             * - marcas-index: /api/proveedores/{proveedor_id}/marcas
             * - lineas-index: /api/proveedores/{proveedor_id}/marcas/{marca}/lineas
             * - unidades-medida-index: /api/proveedores/{proveedor_id}/unidades-medida
             * - tipos-empresa-index: /api/proveedores/{proveedor_id}/tipos-empresa
             */
        });

        // Consulta especial para obtener proveedor por usuario
        Route::get('proveedores/user/{id}', [ProveedorController::class, 'getProveedorByUserId'])->middleware(['audit']);
    });


    /**
     * Gestión de requisiciones global (cliente)
     */
    Route::prefix('requisiciones')->group(function () {
        Route::get('/', [RequisicionController::class, 'index'])->middleware(['audit']);
        Route::post('/', [RequisicionController::class, 'store'])->middleware(['audit']);
        Route::get('{requisicion}', [RequisicionController::class, 'show'])->middleware(['audit']);
        Route::patch('{requisicion}/cancelar', [RequisicionController::class, 'cancelar'])->middleware(['audit']);
    });

    /**
     * Gestión de notificaciones
     */
    Route::prefix('notificaciones')->group(function () {
        Route::get('/', [NotificacionController::class, 'index'])->middleware(['audit']);
        Route::patch('{notificacion}/leer', [NotificacionController::class, 'marcarComoLeida'])->middleware(['audit']);
        Route::patch('marcar-todas-leidas', [NotificacionController::class, 'marcarTodasComoLeidas'])->middleware(['audit']);
        Route::delete('{notificacion}', [NotificacionController::class, 'destroy'])->middleware(['audit']);
    });

    /**
     * Endpoints para Dashboard
     */
    Route::get('dashboard/stats', [DashboardController::class, 'getStats'])->middleware(['audit']);
    Route::get('dashboard/proveedor/{proveedor}/stats', [ProveedorDashboardController::class, 'getStats'])
        ->middleware(['proveedor.access', 'audit']);

    /**
     * Búsqueda global de productos para requisiciones
     */
    Route::get('productos/buscar', [ProductoBusquedaController::class, 'buscar'])->middleware(['audit']);
    Route::get('productos/{producto}/disponibilidad', [ProductoBusquedaController::class, 'verificarDisponibilidad'])->middleware(['audit']);

    // Reportes para proveedores
    Route::prefix('proveedores/{proveedor}/reportes')->middleware(['proveedor.access', 'proveedor.role:GERENTE'])->group(function () {
        Route::get('ventas', [ProveedorReporteController::class, 'ventas']);
        Route::get('productos-populares', [ProveedorReporteController::class, 'productosPopulares']);
        Route::get('requisiciones-mensuales', [ProveedorReporteController::class, 'requisicionesMensuales']);
        Route::get('inventario-sucursales', [ProveedorReporteController::class, 'inventarioSucursales']);
        Route::get('rendimiento-categorias', [ProveedorReporteController::class, 'rendimientoCategorias']);
        Route::get('clientes-activos', [ProveedorReporteController::class, 'clientesActivos']);
        Route::post('exportar', [ProveedorReporteController::class, 'exportar']);
    });

    // Dashboard detallado
    Route::get('dashboard/cliente/stats', [ClienteDashboardController::class, 'getStats']);
    Route::get('dashboard/cliente/resumen-gastos', [ClienteDashboardController::class, 'getResumenGastos']);
    Route::get('dashboard/admin/stats-completas', [AdminDashboardController::class, 'getStatsCompletas']);
    Route::get('dashboard/admin/metricas-rendimiento', [AdminDashboardController::class, 'getMetricasRendimiento']);

    /**
     * Rutas exclusivas para ADMINISTRADORPp
     */
    Route::middleware('role:' . UserRoleEnumerate::ADMINISTRADOR->value)->group(function () {
        Route::get('catalogos-resumen', [AdminHomeControler::class, 'getCatalogosCountItems'])->middleware(['audit']);

        Route::apiResource('users', UserController::class)->middleware(['audit']);
        Route::apiResource('proveedores', ProveedorController::class)->middleware(['audit'])->except(['index']);
        Route::apiResource('sucursales', SucursalController::class)->middleware(['audit'])->except(['index']);
        Route::apiResource('productos', ProductoController::class)->middleware(['audit'])->except(['index']);
        Route::apiResource('imagenes', ProductoImagenController::class)->middleware(['audit'])->except(['index']);
        Route::apiResource('unidades-medida', UnidadMedidaController::class)->middleware(['audit'])->except(['index']);
        Route::apiResource('categorias', CategoriaController::class)->middleware(['audit'])->except(['index']);
        Route::apiResource('lineas', LineaController::class)->middleware(['audit'])->except(['index']);
        Route::apiResource('marcas', MarcaController::class)->middleware(['audit'])->except(['index']); // api/marca ---> admin
        Route::apiResource('tipos-empresa', TipoEmpresaController::class)->middleware(['audit'])->except(['index']);
    });
});



/**
 ************************************************************************************************************************* 
 ************************************************************************************************************************* 
 ************************************************************************************************************************* 
 ************************************************************************************************************************* 
 ************************************************************************************************************************* 
 ************************************************************************************************************************* 
 ************************************************************************************************************************* 
 */



// Rutas para clientes (gestión de sus pedidos)
Route::middleware(['auth:sanctum'])->group(function () {

    // Rutas principales de pedidos
    Route::apiResource('pedidos', PedidoController::class)->except(['update']);

    // Rutas específicas para pedidos
    Route::prefix('pedidos')->group(function () {

        // Actualizar estatus (solo cancelar para clientes)
        Route::patch('{pedido}/status', [PedidoController::class, 'updateStatus'])
            ->name('pedidos.update-status');

        // Cancelar pedido
        Route::patch('{pedido}/cancel', [PedidoController::class, 'cancel'])
            ->name('pedidos.cancel');

        // Duplicar pedido
        Route::post('{pedido}/duplicate', [PedidoController::class, 'duplicar'])
            ->name('pedidos.duplicate');

        // Confirmar recepción
        Route::patch('{pedido}/confirm-reception', [PedidoController::class, 'confirmarRecepcion'])
            ->name('pedidos.confirm-reception');

        // Estadísticas de pedidos
        Route::get('estadisticas', [PedidoController::class, 'estadisticas'])
            ->name('pedidos.estadisticas');

        // Exportar pedidos
        Route::post('export', [PedidoController::class, 'exportar'])
            ->name('pedidos.export');
    });
});

// Rutas para proveedores (gestión de pedidos de sus clientes)
Route::middleware(['auth:sanctum'])->group(function () {

    Route::prefix('proveedores/{proveedor}')->group(function () {

        // Dashboard de pedidos
        Route::get('pedidos/dashboard', [ProveedorPedidoController::class, 'dashboard'])
            ->name('proveedor.pedidos.dashboard');

        // Listar pedidos del proveedor
        Route::get('pedidos', [ProveedorPedidoController::class, 'index'])
            ->name('proveedor.pedidos.index');

        // Ver pedido específico
        Route::get('pedidos/{pedido}', [ProveedorPedidoController::class, 'show'])
            ->name('proveedor.pedidos.show');

        // Actualizar estatus del pedido
        Route::patch('pedidos/{pedido}/status', [ProveedorPedidoController::class, 'updateStatus'])
            ->name('proveedor.pedidos.update-status');

        // Preparar envío
        Route::patch('pedidos/{pedido}/prepare-shipment', [ProveedorPedidoController::class, 'prepareShipment'])
            ->name('proveedor.pedidos.prepare-shipment');

        // Confirmar entrega
        Route::patch('pedidos/{pedido}/confirm-delivery', [ProveedorPedidoController::class, 'confirmDelivery'])
            ->name('proveedor.pedidos.confirm-delivery');

        // Rechazar pedido
        Route::patch('pedidos/{pedido}/reject', [ProveedorPedidoController::class, 'rechazar'])
            ->name('proveedor.pedidos.reject');

        // Exportar pedidos del proveedor
        Route::post('pedidos/export', [ProveedorPedidoController::class, 'exportar'])
            ->name('proveedor.pedidos.export');
    });
});

// Rutas públicas o con autenticación específica
Route::middleware(['throttle:60,1'])->group(function () {

    // Webhook para actualizaciones de tracking (transportistas)
    Route::post('pedidos/{pedido}/tracking-update', [PedidoController::class, 'trackingUpdate'])
        ->name('pedidos.tracking-update');

    // Consulta pública de estado de pedido (con token)
    Route::get('pedidos/{pedido}/status/{token}', [PedidoController::class, 'publicStatus'])
        ->name('pedidos.public-status');
});

// Rutas administrativas
Route::middleware(['auth:sanctum', 'role:ADMINISTRADOR'])->group(function () {

    Route::prefix('admin/pedidos')->group(function () {

        // Listar todos los pedidos
        Route::get('/', [PedidoController::class, 'adminIndex'])
            ->name('admin.pedidos.index');

        // Estadísticas generales
        Route::get('stats', [PedidoController::class, 'adminStats'])
            ->name('admin.pedidos.stats');

        // Forzar cambio de estatus
        Route::patch('{pedido}/force-status', [PedidoController::class, 'forceStatus'])
            ->name('admin.pedidos.force-status');

        // Eliminar pedido
        Route::delete('{pedido}', [PedidoController::class, 'destroy'])
            ->name('admin.pedidos.destroy');

        // Reportes avanzados
        Route::get('reports', [PedidoController::class, 'adminReports'])
            ->name('admin.pedidos.reports');

        // Auditoria de pedidos
        Route::get('{pedido}/audit', [PedidoController::class, 'auditLog'])
            ->name('admin.pedidos.audit');
    });
});

/*
|--------------------------------------------------------------------------
| Rutas de desarrollo y testing
|--------------------------------------------------------------------------
|
| Estas rutas solo están disponibles en ambiente de desarrollo
|
*/

if (app()->environment('local', 'testing')) {
    Route::prefix('test/pedidos')->group(function () {

        // Generar pedidos de prueba
        Route::post('generate-test-data', [PedidoController::class, 'generateTestData'])
            ->name('test.pedidos.generate');

        // Simular webhook de transportista
        Route::post('simulate-tracking', [PedidoController::class, 'simulateTracking'])
            ->name('test.pedidos.simulate-tracking');

        // Benchmark de rendimiento
        Route::get('performance', [PedidoController::class, 'performanceTest'])
            ->name('test.pedidos.performance');
    });
}

/*
|--------------------------------------------------------------------------
| Rutas de integración con servicios externos
|--------------------------------------------------------------------------
*/

// Integración con sistema de facturación
Route::prefix('integration')->middleware(['auth:sanctum'])->group(function () {

    // Sincronizar con sistema de facturación
    Route::post('pedidos/{pedido}/sync-billing', [PedidoController::class, 'syncBilling'])
        ->name('integration.pedidos.sync-billing');

    // Generar factura automática
    Route::post('pedidos/{pedido}/generate-invoice', [PedidoController::class, 'generateInvoice'])
        ->name('integration.pedidos.generate-invoice');

    // Webhook de confirmación de pago
    Route::post('pedidos/{pedido}/payment-confirmed', [PedidoController::class, 'paymentConfirmed'])
        ->name('integration.pedidos.payment-confirmed');
});

// Rutas de notificaciones
Route::prefix('notifications')->middleware(['auth:sanctum'])->group(function () {

    // Marcar notificación como leída
    Route::patch('pedidos/{pedido}/mark-read', [PedidoController::class, 'markNotificationRead'])
        ->name('notifications.pedidos.mark-read');

    // Configurar alertas de pedidos
    Route::post('pedidos/alerts', [PedidoController::class, 'configureAlerts'])
        ->name('notifications.pedidos.configure-alerts');
});

/*
|--------------------------------------------------------------------------
| Middleware personalizado para rutas de pedidos
|--------------------------------------------------------------------------
*/

// Middleware para verificar ownership de pedidos
Route::middleware(['auth:sanctum', 'verify.pedido.owner'])->group(function () {

    // Rutas que requieren verificación de propiedad
    Route::get('my-pedidos/{pedido}/detailed', [PedidoController::class, 'detailedView'])
        ->name('my-pedidos.detailed');

    Route::patch('my-pedidos/{pedido}/update-preferences', [PedidoController::class, 'updatePreferences'])
        ->name('my-pedidos.update-preferences');
});

// Middleware para verificar acceso de proveedor
Route::middleware(['auth:sanctum', 'verify.proveedor.access'])->group(function () {

    // Rutas que requieren acceso específico de proveedor
    Route::get('proveedor-pedidos/{pedido}/internal-notes', [ProveedorPedidoController::class, 'internalNotes'])
        ->name('proveedor-pedidos.internal-notes');

    Route::post('proveedor-pedidos/{pedido}/add-note', [ProveedorPedidoController::class, 'addInternalNote'])
        ->name('proveedor-pedidos.add-note');
});

/*
|--------------------------------------------------------------------------
| Rutas de consulta y reporting
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum'])->group(function () {

    // Reportes de pedidos por cliente
    Route::get('reports/my-pedidos', [PedidoController::class, 'myPedidosReport'])
        ->name('reports.my-pedidos');

    // Reportes de pedidos por proveedor
    Route::get('reports/proveedor/{proveedor}/pedidos', [ProveedorPedidoController::class, 'proveedorPedidosReport'])
        ->name('reports.proveedor-pedidos');

    // Análisis de tendencias
    Route::get('analytics/pedidos-trends', [PedidoController::class, 'pedidosTrends'])
        ->name('analytics.pedidos-trends');

    // Comparativas de rendimiento
    Route::get('analytics/performance-comparison', [PedidoController::class, 'performanceComparison'])
        ->name('analytics.performance-comparison');
});

/*
|--------------------------------------------------------------------------
| Rutas de búsqueda y filtrado
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum'])->group(function () {

    // Búsqueda avanzada de pedidos
    Route::post('pedidos/search', [PedidoController::class, 'advancedSearch'])
        ->name('pedidos.advanced-search');

    // Filtros guardados
    Route::get('pedidos/saved-filters', [PedidoController::class, 'savedFilters'])
        ->name('pedidos.saved-filters');

    Route::post('pedidos/save-filter', [PedidoController::class, 'saveFilter'])
        ->name('pedidos.save-filter');

    // Búsqueda de pedidos por proveedor
    Route::post('proveedores/{proveedor}/pedidos/search', [ProveedorPedidoController::class, 'advancedSearch'])
        ->name('proveedor.pedidos.advanced-search');
});

/*
|--------------------------------------------------------------------------
| Rutas de exportación y documentos
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum'])->group(function () {

    // Generar PDF de pedido
    Route::get('pedidos/{pedido}/pdf', [PedidoController::class, 'generatePDF'])
        ->name('pedidos.pdf');

    // Descargar comprobante de pedido
    Route::get('pedidos/{pedido}/receipt', [PedidoController::class, 'downloadReceipt'])
        ->name('pedidos.receipt');

    // Generar etiqueta de envío
    Route::get('pedidos/{pedido}/shipping-label', [PedidoController::class, 'shippingLabel'])
        ->name('pedidos.shipping-label');

    // Documentos de entrega
    Route::get('pedidos/{pedido}/delivery-documents', [PedidoController::class, 'deliveryDocuments'])
        ->name('pedidos.delivery-documents');
});
