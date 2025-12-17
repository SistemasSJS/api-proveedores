<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProductoImagenController;
use App\Http\Controllers\UnidadMedidaController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\MarcaController;
use App\Http\Controllers\TipoEmpresaController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\AdminHomeControler;
use App\Http\Controllers\AdminDashboardController;
use App\Enums\UserRoleEnumerate;
use App\Http\Controllers\AdminPedidosController;
use App\Http\Controllers\AdminProveedorController;
use App\Http\Controllers\ProveedorUsuarioController;

/*
|--------------------------------------------------------------------------
| RUTAS ESPECÍFICAS PARA ROL: ADMINISTRADOR
|--------------------------------------------------------------------------
| Estas rutas solo son accesibles para usuarios con rol ADMINISTRADOR
*/

Route::middleware(['auth:sanctum', 'role:' . UserRoleEnumerate::ADMINISTRADOR->value])->prefix('admin')->group(function () {

    /**
     * GESTIÓN GENERAL DE USUARIOS
     */
    Route::apiResource('usuarios', UserController::class)->middleware(['audit']);
    
    /**
     * REASIGNACIÓN DE USUARIOS A PROVEEDORES
     */
    Route::post('usuarios/{user}/reasignar', [ProveedorUsuarioController::class, 'reasignarUsuario'])->middleware(['audit']);

    /**
     * GESTIÓN DE CATÁLOGOS MAESTROS
     */
    Route::prefix('catalogos')->group(function () {
        // Proveedores
        Route::get('proveedores', [AdminProveedorController::class, 'index']);
        Route::post('proveedores', [AdminProveedorController::class, 'store'])->middleware('audit');
        Route::get('proveedores/{proveedor}', [AdminProveedorController::class, 'show']);
        Route::put('proveedores/{proveedor}', [AdminProveedorController::class, 'update'])->middleware('audit');
        Route::patch('proveedores/{proveedor}', [AdminProveedorController::class, 'update'])->middleware('audit');
        Route::delete('proveedores/{proveedor}', [AdminProveedorController::class, 'destroy'])->middleware('audit');
        Route::get('proveedores/{proveedor}/productos', [AdminProveedorController::class, 'destroy'])->middleware('audit');
        Route::get('proveedores/all/count-categorias', [AdminProveedorController::class, 'proveedoresConCategoriasConSubcatCountProductos'])->middleware('audit');

        // Sucursales
        Route::get('sucursales', [SucursalController::class, 'index']);
        Route::post('sucursales', [SucursalController::class, 'store'])->middleware('audit');
        Route::get('sucursales/groupedByProveedor', [SucursalController::class, 'indexGroupedByProveedor'])->middleware('audit');
        Route::get('sucursales/{sucursal}', [SucursalController::class, 'show']);
        Route::put('sucursales/{sucursal}', [SucursalController::class, 'update'])->middleware('audit');
        Route::patch('sucursales/{sucursal}', [SucursalController::class, 'update'])->middleware('audit');
        Route::delete('sucursales/{sucursal}', [SucursalController::class, 'destroy'])->middleware('audit');

        // Productos
        Route::get('productos', [ProductoController::class, 'index']);
        Route::post('productos', [ProductoController::class, 'store'])->middleware('audit');
        Route::get('productos/{producto}', [ProductoController::class, 'show']);
        Route::put('productos/{producto}', [ProductoController::class, 'update'])->middleware('audit');
        Route::patch('productos/{producto}', [ProductoController::class, 'update'])->middleware('audit');
        Route::delete('productos/{producto}', [ProductoController::class, 'destroy'])->middleware('audit');

        // Imágenes
        Route::get('imagenes', [ProductoImagenController::class, 'index']);
        Route::post('imagenes', [ProductoImagenController::class, 'store'])->middleware('audit');
        Route::get('imagenes/{imagen}', [ProductoImagenController::class, 'show']);
        Route::put('imagenes/{imagen}', [ProductoImagenController::class, 'update'])->middleware('audit');
        Route::patch('imagenes/{imagen}', [ProductoImagenController::class, 'update'])->middleware('audit');
        Route::delete('imagenes/{imagen}', [ProductoImagenController::class, 'destroy'])->middleware('audit');

        // Unidades de medida
        Route::get('unidades-medida', [UnidadMedidaController::class, 'index']);
        Route::post('unidades-medida', [UnidadMedidaController::class, 'store'])->middleware('audit');
        Route::get('unidades-medida/{unidad_medida}', [UnidadMedidaController::class, 'show']);
        Route::put('unidades-medida/{unidad_medida}', [UnidadMedidaController::class, 'update'])->middleware('audit');
        Route::patch('unidades-medida/{unidad_medida}', [UnidadMedidaController::class, 'update'])->middleware('audit');
        Route::delete('unidades-medida/{unidad_medida}', [UnidadMedidaController::class, 'destroy'])->middleware('audit');

        // Categorías
        Route::get('categorias', [CategoriaController::class, 'index']);
        Route::post('categorias', [CategoriaController::class, 'store'])->middleware('audit');
        Route::get('categorias/{categoria}', [CategoriaController::class, 'show']);
        Route::put('categorias/{categoria}', [CategoriaController::class, 'update'])->middleware('audit');
        Route::patch('categorias/{categoria}', [CategoriaController::class, 'update'])->middleware('audit');
        Route::delete('categorias/{categoria}', [CategoriaController::class, 'destroy'])->middleware('audit');

        // Marcas
        Route::get('marcas', [MarcaController::class, 'index']);
        Route::post('marcas', [MarcaController::class, 'store'])->middleware('audit');
        Route::get('marcas/{marca}', [MarcaController::class, 'show']);
        Route::put('marcas/{marca}', [MarcaController::class, 'update'])->middleware('audit');
        Route::patch('marcas/{marca}', [MarcaController::class, 'update'])->middleware('audit');
        Route::delete('marcas/{marca}', [MarcaController::class, 'destroy'])->middleware('audit');

        // Tipos de empresa
        Route::get('tipos-empresa', [TipoEmpresaController::class, 'index']);
        Route::post('tipos-empresa', [TipoEmpresaController::class, 'store'])->middleware('audit');
        Route::get('tipos-empresa/{tipo_empresa}', [TipoEmpresaController::class, 'show']);
        Route::put('tipos-empresa/{tipo_empresa}', [TipoEmpresaController::class, 'update'])->middleware('audit');
        Route::patch('tipos-empresa/{tipo_empresa}', [TipoEmpresaController::class, 'update'])->middleware('audit');
        Route::delete('tipos-empresa/{tipo_empresa}', [TipoEmpresaController::class, 'destroy'])->middleware('audit');
    });

    /**
     * GESTIÓN ADMINISTRATIVA DE PEDIDOS
     */
    Route::prefix('pedidos')->group(function () {
        Route::get('/', [AdminPedidosController::class, 'adminIndex'])
            ->middleware(['audit'])
            ->name('admin.pedidos.index');

        Route::get('stats', [AdminPedidosController::class, 'adminStats'])
            ->middleware(['audit'])
            ->name('admin.pedidos.stats');

        Route::patch('{pedido}/force-status', [AdminPedidosController::class, 'forceStatus'])
            ->middleware(['audit'])
            ->name('admin.pedidos.force-status');

        Route::delete('{pedido}', [AdminPedidosController::class, 'destroy'])
            ->middleware(['audit'])
            ->name('admin.pedidos.destroy');

        Route::get('reports', [AdminPedidosController::class, 'adminReports'])
            ->middleware(['audit'])
            ->name('admin.pedidos.reports');

        Route::get('{pedido}/audit', [AdminPedidosController::class, 'auditLog'])
            ->middleware(['audit'])
            ->name('admin.pedidos.audit');
    });

    /**
     * DASHBOARD ADMINISTRATIVO
     */
    Route::prefix('dashboard')->group(function () {
        Route::get('catalogos-resumen', [AdminHomeControler::class, 'getCatalogosCountItems'])->middleware(['audit']);
        Route::get('stats-completas', [AdminDashboardController::class, 'getStatsCompletas'])->middleware(['audit']);
        Route::get('metricas-rendimiento', [AdminDashboardController::class, 'getMetricasRendimiento'])->middleware(['audit']);
    });

    /**
     * INTEGRACIÓN CON SERVICIOS EXTERNOS
     */
    Route::prefix('integracion')->group(function () {
        Route::post('pedidos/{pedido}/sync-billing', [PedidoController::class, 'syncBilling'])
            ->middleware(['audit'])
            ->name('admin.integration.pedidos.sync-billing');

        Route::post('pedidos/{pedido}/generate-invoice', [PedidoController::class, 'generateInvoice'])
            ->middleware(['audit'])
            ->name('admin.integration.pedidos.generate-invoice');

        Route::post('pedidos/{pedido}/payment-confirmed', [PedidoController::class, 'paymentConfirmed'])
            ->middleware(['audit'])
            ->name('admin.integration.pedidos.payment-confirmed');
    });
});

/**
 * RUTAS GLOBALES ADMINISTRATIVAS (COMPATIBILIDAD)
 * Mantienen el comportamiento existente
 */
Route::middleware(['auth:sanctum', 'role:' . UserRoleEnumerate::ADMINISTRADOR->value])->group(function () {

    // Resumen de catálogos (compatibilidad)
    Route::get('catalogos-resumen', [AdminHomeControler::class, 'getCatalogosCountItems'])->middleware(['audit']);

    // API Resources (compatibilidad)
    Route::apiResource('users', UserController::class)->middleware(['audit']);
    Route::apiResource('proveedores', AdminProveedorController::class)->middleware(['audit'])->except(['index']);
    Route::apiResource('sucursales', SucursalController::class)->middleware(['audit'])->except(['index']);
    Route::apiResource('productos', ProductoController::class)->middleware(['audit'])->except(['index']);
    Route::apiResource('imagenes', ProductoImagenController::class)->middleware(['audit'])->except(['index']);
    Route::apiResource('unidades-medida', UnidadMedidaController::class)->middleware(['audit'])->except(['index']);
    Route::apiResource('categorias', CategoriaController::class)->middleware(['audit'])->except(['index']);
    Route::apiResource('marcas', MarcaController::class)->middleware(['audit'])->except(['index']);
    Route::apiResource('tipos-empresa', TipoEmpresaController::class)->middleware(['audit'])->except(['index']);

    // Dashboard stats (compatibilidad)
    Route::get('dashboard/admin/stats-completas', [AdminDashboardController::class, 'getStatsCompletas']);
    Route::get('dashboard/admin/metricas-rendimiento', [AdminDashboardController::class, 'getMetricasRendimiento']);
});

// /**
//  * RUTAS ADMINISTRATIVAS DE PEDIDOS (COMPATIBILIDAD)
//  */
// Route::middleware(['auth:sanctum', 'role:ADMINISTRADOR'])->group(function () {

//     Route::prefix('admin/pedidos')->group(function () {

//         // Listar todos los pedidos (compatibilidad)
//         Route::get('/', [AdminPedidosController::class, 'adminIndex'])
//             ->name('admin.pedidos.index');

//         // Estadísticas generales (compatibilidad)
//         Route::get('stats', [AdminPedidosController::class, 'adminStats'])
//             ->name('admin.pedidos.stats');

//         // Forzar cambio de estatus (compatibilidad)
//         Route::patch('{pedido}/force-status', [AdminPedidosController::class, 'forceStatus'])
//             ->name('admin.pedidos.force-status');

//         // Eliminar pedido (compatibilidad)
//         Route::delete('{pedido}', [AdminPedidosController::class, 'destroy'])
//             ->name('admin.pedidos.destroy');

//         // Reportes avanzados (compatibilidad)
//         Route::get('reports', [AdminPedidosController::class, 'adminReports'])
//             ->name('admin.pedidos.reports');

//         // Auditoria de pedidos (compatibilidad)
//         Route::get('{pedido}/audit', [AdminPedidosController::class, 'auditLog'])
//             ->name('admin.pedidos.audit');
//     });
// });

// /**
//  * INTEGRACIÓN CON SERVICIOS EXTERNOS (COMPATIBILIDAD)
//  */
// Route::prefix('integration')->middleware(['auth:sanctum'])->group(function () {

//     // Sincronizar con sistema de facturación (compatibilidad)
//     Route::post('pedidos/{pedido}/sync-billing', [PedidoController::class, 'syncBilling'])
//         ->name('integration.pedidos.sync-billing');

//     // Generar factura automática (compatibilidad)
//     Route::post('pedidos/{pedido}/generate-invoice', [PedidoController::class, 'generateInvoice'])
//         ->name('integration.pedidos.generate-invoice');

//     // Webhook de confirmación de pago (compatibilidad)
//     Route::post('pedidos/{pedido}/payment-confirmed', [PedidoController::class, 'paymentConfirmed'])
//         ->name('integration.pedidos.payment-confirmed');
// });
