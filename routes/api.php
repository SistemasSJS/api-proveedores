<?php

use App\Enums\UserRoleEnumerate;
use App\Http\Controllers\AdminDashboardController;
use Illuminate\Support\Facades\Route;

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
        Route::get('logout', [AuthController::class, 'logout']);
        Route::post('update-img-perfil', [AuthController::class, 'update_foto_perfil']);
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
 * Rutas protegidas con auth:sanctum
 */
Route::middleware('auth:sanctum')->group(function () {

    /**
     * Rutas con roles GERENTE y ADMINISTRADOR
     */
    Route::middleware('role:' . UserRoleEnumerate::GERENTE->value . ',' . UserRoleEnumerate::ADMINISTRADOR->value)->group(function () {

        /**
         * Gestión de proveedores
         */
        Route::prefix('proveedores')->group(function () {

            // Rutas CRUD y perfil para proveedores
            Route::controller(ProveedorController::class)->group(function () {
                Route::get('/', 'index')->middleware(['audit']);
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
             * Gestión de marcas del proveedor
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
                // Route::middleware(['proveedor.linea', 'audit'])->group(function () {
                //     Route::get('{linea}', [ProveedorLineaController::class, 'show']);
                //     Route::patch('{linea}', [ProveedorLineaController::class, 'update']);
                //     Route::delete('{linea}', [ProveedorLineaController::class, 'destroy']);
                // });
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
     * Rutas exclusivas para ADMINISTRADOR
     */
    Route::middleware('role:' . UserRoleEnumerate::ADMINISTRADOR->value)->group(function () {
        Route::get('catalogos-resumen', [AdminHomeControler::class, 'getCatalogosCountItems'])->middleware(['audit']);

        Route::apiResource('users', UserController::class)->middleware(['audit']);
        Route::apiResource('proveedores', ProveedorController::class)->middleware(['audit']);
        Route::apiResource('sucursales', SucursalController::class)->middleware(['audit']);
        Route::apiResource('productos', ProductoController::class)->middleware(['audit']);
        Route::apiResource('imagenes', ProductoImagenController::class)->middleware(['audit']);
        Route::apiResource('unidades-medida', UnidadMedidaController::class)->middleware(['audit']);
        Route::apiResource('categorias', CategoriaController::class)->middleware(['audit']);
        Route::apiResource('lineas', LineaController::class)->middleware(['audit']);
        Route::apiResource('marcas', MarcaController::class)->middleware(['audit']);
        Route::apiResource('tipos-empresa', TipoEmpresaController::class)->middleware(['audit']);
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
});
