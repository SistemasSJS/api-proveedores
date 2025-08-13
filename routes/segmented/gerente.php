<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ProveedorUsuarioController;
use App\Http\Controllers\ProveedorProductoController;
use App\Http\Controllers\ProveedorCategoriaController;
use App\Http\Controllers\ProveedorMarcaController;
use App\Http\Controllers\ProveedorSucursalController;
use App\Http\Controllers\SucursalProductoController;
use App\Http\Controllers\ProveedorRequisicionController;
use App\Http\Controllers\ProveedorPedidoController;
use App\Http\Controllers\ProductoImportController;
use App\Http\Controllers\ProveedorReporteController;
use App\Http\Controllers\ProveedorDashboardController;
use App\Http\Controllers\ImportHistoryController;
use App\Enums\UserRoleEnumerate;
use App\Http\Controllers\ImportStatsController;
use App\Http\Controllers\ProveedorUnidadMedidaController;
use App\Http\Controllers\CsvImportController;

/**
 * GESTIÓN DE PROVEEDORES
 */
Route::prefix('proveedores')
    ->middleware(['auth:sanctum', 'role:' . UserRoleEnumerate::GERENTE->value])
    ->group(function () {

        /**
         * CRUD BASICO
         */
        Route::post('/', [ProveedorController::class, 'store'])->middleware(['audit']);
        Route::get('{proveedor}', [ProveedorController::class, 'show'])->middleware(['api.access', 'audit']);
        Route::patch('{proveedor}', [ProveedorController::class, 'update'])->middleware(['api.access', 'audit']);
        Route::delete('{proveedor}', [ProveedorController::class, 'destroy'])->middleware(['api.access', 'audit']);
        Route::post('{proveedor}/logo', [ProveedorController::class, 'updateLogo'])->middleware(['api.access', 'audit']);

        // Consultas especiales
        Route::get('user/{id}', [ProveedorController::class, 'getProveedorByUserId'])->middleware(['audit']);

        /**
         * USUARIOS DEL PROVEEDOR
         */
        Route::prefix('{proveedor}/users')->middleware(['proveedor.access'])->group(function () {
            Route::get('/', [ProveedorUsuarioController::class, 'index'])->middleware(['api.access', 'audit']);
            Route::post('/', [ProveedorUsuarioController::class, 'store'])->middleware(['api.access', 'audit']);

            Route::middleware(['api.access', 'proveedor.user', 'audit'])->group(function () {
                Route::get('{user}', [ProveedorUsuarioController::class, 'show']);
                Route::patch('{user}', [ProveedorUsuarioController::class, 'update']);
                Route::delete('{user}', [ProveedorUsuarioController::class, 'destroy']);
                Route::post('{user}/logo', [ProveedorUsuarioController::class, 'updateLogo']);
            });
        });

        /**
         * PRODUCTOS DEL PROVEEDOR
         */
        Route::prefix('{proveedor}/productos')->middleware(['proveedor.access'])->group(function () {
            Route::get('/', [ProveedorProductoController::class, 'index'])->middleware(['audit']);
            Route::post('/', [ProveedorProductoController::class, 'store'])->middleware(['audit']);
            Route::post('/bulk', [ProveedorProductoController::class, 'bulkStore'])->middleware(['audit']);
            Route::post('/bulk-json', [ProveedorProductoController::class, 'bulkStoreJson'])->middleware(['audit']);

            // Nueva ruta de importación integrada con el servicio
            Route::post('/import', [ImportHistoryController::class, 'import'])->middleware(['audit']);

            // Nuevos endpoints de importación CSV
            Route::post('/import-preview', [ImportHistoryController::class, 'importPreview'])->middleware(['audit']);
            Route::post('/import-confirm', [ImportHistoryController::class, 'importConfirm'])->middleware(['audit']);

            Route::middleware(['proveedor.producto', 'audit'])->group(function () {
                Route::get('{producto}', [ProveedorProductoController::class, 'show']);
                Route::patch('{producto}', [ProveedorProductoController::class, 'update']);
                Route::delete('{producto}', [ProveedorProductoController::class, 'destroy']);
                Route::post('{producto}/logo', [ProveedorProductoController::class, 'updateLogo']);
            });
        });

        /**
         * CATEGORÍAS DEL PROVEEDOR
         */
        Route::prefix('{proveedor}/categorias')->middleware(['proveedor.access'])->group(function () {
            Route::get('/', [ProveedorCategoriaController::class, 'index'])->middleware(['audit']);
            Route::post('/', [ProveedorCategoriaController::class, 'store'])->middleware(['audit']);
            Route::get('/all', [ProveedorCategoriaController::class, 'all'])->middleware(['audit']);

            Route::middleware(['proveedor.categoria', 'audit'])->group(function () {
                Route::get('{categoria}', [ProveedorCategoriaController::class, 'show']);
                Route::patch('{categoria}', [ProveedorCategoriaController::class, 'update']);
                Route::delete('{categoria}', [ProveedorCategoriaController::class, 'destroy']);
                Route::post('{categoria}/logo', [ProveedorCategoriaController::class, 'updateLogo']);
                Route::get('{categoria}/subcategorias', [ProveedorCategoriaController::class, 'index_sub_categorias']);
            });
        });

        /**
         * MARCAS DEL PROVEEDOR
         */
        Route::prefix('{proveedor}/marcas')->middleware(['proveedor.access'])->group(function () {
            Route::get('/', [ProveedorMarcaController::class, 'index'])->middleware(['audit']);
            Route::post('/', [ProveedorMarcaController::class, 'store'])->middleware(['audit']);
            Route::get('/all', [ProveedorMarcaController::class, 'all'])->middleware(['audit']);

            Route::middleware(['proveedor.marca', 'audit'])->group(function () {
                Route::get('{marca}', [ProveedorMarcaController::class, 'show']);
                Route::patch('{marca}', [ProveedorMarcaController::class, 'update']);
                Route::delete('{marca}', [ProveedorMarcaController::class, 'destroy']);
                Route::post('{marca}/logo', [ProveedorMarcaController::class, 'updateLogo']);
                Route::get('{marca}/lineas', [ProveedorMarcaController::class, 'index_lineas_por_marca']);
            });
        });

        /**
         * UNIDA MEDIDA DEL PROVEEDOR
         */
        Route::prefix('{proveedor}/unidades')->middleware(['proveedor.access'])->group(function () {
            Route::get('/', [ProveedorUnidadMedidaController::class, 'index'])->middleware(['audit']);
            Route::post('/', [ProveedorUnidadMedidaController::class, 'store'])->middleware(['audit']);
            Route::get('/all', [ProveedorUnidadMedidaController::class, 'all'])->middleware(['audit']);

            Route::middleware(['proveedor.unidad', 'audit'])->group(function () {
                Route::get('{unidad}', [ProveedorUnidadMedidaController::class, 'show']);
                Route::patch('{unidad}', [ProveedorUnidadMedidaController::class, 'update']);
                Route::delete('{unidad}', [ProveedorUnidadMedidaController::class, 'destroy']);
            });
        });


        /**
         * SUCURSALES DEL PROVEEDOR
         */
        Route::prefix('{proveedor}/sucursales')->middleware(['proveedor.access'])->group(function () {
            Route::get('/', [ProveedorSucursalController::class, 'index'])->middleware(['audit']);
            Route::post('/', [ProveedorSucursalController::class, 'store'])->middleware(['audit']);

            Route::middleware(['proveedor.sucursal', 'audit'])->group(function () {
                Route::get('{sucursal}', [ProveedorSucursalController::class, 'show']);
                Route::delete('{sucursal}', [ProveedorSucursalController::class, 'destroy']);
                Route::patch('{sucursal}', [ProveedorSucursalController::class, 'update']);

                /**
                 * PRODUCTOS POR SUCURSAL
                 */
                Route::prefix('productos')->group(function () {
                    Route::get('/', [SucursalProductoController::class, 'index'])->middleware(['audit']);
                    Route::post('asignar', [SucursalProductoController::class, 'asignarProductos'])->middleware(['audit']);
                    Route::delete('desasignar', [SucursalProductoController::class, 'desasignarProductos'])->middleware(['audit']);
                    Route::patch('{producto}', [SucursalProductoController::class, 'updateStock'])->middleware(['audit']);
                });
            });
        });

        /**
         * REQUISICIONES DEL PROVEEDOR
         */
        Route::prefix('{proveedor}/requisiciones')->middleware(['proveedor.access'])->group(function () {
            Route::get('/', [ProveedorRequisicionController::class, 'index'])->middleware(['audit']);
            Route::get('{requisicion}', [ProveedorRequisicionController::class, 'show'])->middleware(['audit']);
            Route::patch('{requisicion}/estatus', [ProveedorRequisicionController::class, 'cambiarEstatus'])->middleware(['audit']);
            Route::post('{requisicion}/cotizar', [ProveedorRequisicionController::class, 'generarCotizacion'])->middleware(['audit']);
        });

        /**
         * PEDIDOS DEL PROVEEDOR
         */
        Route::prefix('{proveedor}/pedidos')->middleware(['proveedor.access'])->group(function () {
            Route::get('dashboard', [ProveedorPedidoController::class, 'dashboard'])->middleware(['audit'])->name('gerente.proveedor.pedidos.dashboard');
            Route::get('/', [ProveedorPedidoController::class, 'index'])->middleware(['audit'])->name('gerente.proveedor.pedidos.index');
            Route::get('{pedido}', [ProveedorPedidoController::class, 'show'])->middleware(['audit'])->name('gerente.proveedor.pedidos.show');
            Route::patch('{pedido}/status', [ProveedorPedidoController::class, 'updateStatus'])->middleware(['audit'])->name('gerente.proveedor.pedidos.update-status');
            Route::patch('{pedido}/prepare-shipment', [ProveedorPedidoController::class, 'prepareShipment'])->middleware(['audit'])->name('gerente.proveedor.pedidos.prepare-shipment');
            Route::patch('{pedido}/confirm-delivery', [ProveedorPedidoController::class, 'confirmDelivery'])->middleware(['audit'])->name('gerente.proveedor.pedidos.confirm-delivery');
            Route::patch('{pedido}/reject', [ProveedorPedidoController::class, 'rechazar'])->middleware(['audit'])->name('gerente.proveedor.pedidos.reject');
            Route::post('export', [ProveedorPedidoController::class, 'exportar'])->middleware(['audit'])->name('gerente.proveedor.pedidos.export');
        });

        /**
         * HISTORIAL DE IMPORTACIONES
         */
        Route::prefix('{proveedor}/import-history')->middleware(['proveedor.access'])->group(function () {
            Route::get('/', [ImportHistoryController::class, 'index'])->middleware(['audit']);
            Route::post('/', [ImportHistoryController::class, 'store'])->middleware(['audit']);
            Route::get('/statistics', [ImportStatsController::class, 'dashboard'])->middleware(['audit']);
            Route::get('{importHistory}', [ImportHistoryController::class, 'show'])->middleware(['audit']);
            Route::get('{importHistory}/detailed', [ImportHistoryController::class, 'showDetailed'])->middleware(['audit']);
            Route::patch('{importHistory}', [ImportHistoryController::class, 'update'])->middleware(['audit']);
            Route::delete('{importHistory}', [ImportHistoryController::class, 'destroy'])->middleware(['audit']);
        });

        /**
         * IMPORTACIONES DE PRODUCTOS (Mantenemos compatibilidad)
         */
        Route::prefix('{proveedor}/imports')->middleware(['proveedor.access'])->group(function () {
            Route::post('products', [ProductoImportController::class, 'upload'])->middleware(['audit']);
            Route::get('products', [ProductoImportController::class, 'list'])->middleware(['audit']);
            Route::get('{audit}', [ProductoImportController::class, 'status'])->middleware(['audit']);
            Route::get('{audit}/logs', [ProductoImportController::class, 'status'])->middleware(['audit']);
            Route::post('{audit}/confirm', [ProductoImportController::class, 'confirm'])->middleware(['audit']);
        });

        /**
         * CSV IMPORT ROUTES
         */
        Route::prefix('{proveedor}/csv-import')->middleware(['proveedor.access'])->group(function () {
            Route::post('/upload', [CsvImportController::class, 'upload'])->middleware(['audit']);
            Route::post('/validate-producto', [CsvImportController::class, 'validateProducto'])->middleware(['audit']);
            Route::post('/confirm', [CsvImportController::class, 'confirm'])->middleware(['audit']);
        });

        /**
         * REPORTES DEL PROVEEDOR
         */
        Route::prefix('{proveedor}/reportes')->middleware(['proveedor.access', 'proveedor.role:GERENTE'])->group(function () {
            Route::get('ventas', [ProveedorReporteController::class, 'ventas'])->middleware(['audit']);
            Route::get('productos-populares', [ProveedorReporteController::class, 'productosPopulares'])->middleware(['audit']);
            Route::get('requisiciones-mensuales', [ProveedorReporteController::class, 'requisicionesMensuales'])->middleware(['audit']);
            Route::get('inventario-sucursales', [ProveedorReporteController::class, 'inventarioSucursales'])->middleware(['audit']);
            Route::get('rendimiento-categorias', [ProveedorReporteController::class, 'rendimientoCategorias'])->middleware(['audit']);
            Route::get('clientes-activos', [ProveedorReporteController::class, 'clientesActivos'])->middleware(['audit']);
            Route::post('exportar', [ProveedorReporteController::class, 'exportar'])->middleware(['audit']);
        });

        /**
         * DASHBOARD PROVEEDOR
         */
        Route::get('{proveedor}/dashboard/stats', [ProveedorDashboardController::class, 'getStats'])
            ->middleware(['proveedor.access', 'audit']);

        Route::get('imports/products/template', [ProductoImportController::class, 'downloadTemplate']);
    });
