<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProveedorPedidoController;

/*
|--------------------------------------------------------------------------
| RUTAS DE COMPATIBILIDAD (MANTIENEN COMPORTAMIENTO EXISTENTE)
|--------------------------------------------------------------------------
| Estas rutas mantienen la funcionalidad existente sin cambios
*/

/**
 * RUTAS GLOBALES DE PROVEEDORES (COMPATIBILIDAD COMPLETA)
 * Mantienen exactamente el mismo comportamiento que las rutas originales
 */
Route::middleware(['auth:sanctum'])->group(function () {

    Route::prefix('proveedores/{proveedor}')->group(function () {

        // Dashboard de pedidos (compatibilidad)
        Route::get('pedidos/dashboard', [ProveedorPedidoController::class, 'dashboard'])
            ->name('proveedor.pedidos.dashboard');

        // Listar pedidos del proveedor (compatibilidad)
        Route::get('pedidos', [ProveedorPedidoController::class, 'index'])
            ->name('proveedor.pedidos.index');

        // Ver pedido específico (compatibilidad)
        Route::get('pedidos/{pedido}', [ProveedorPedidoController::class, 'show'])
            ->name('proveedor.pedidos.show');

        // Actualizar estatus del pedido (compatibilidad)
        Route::patch('pedidos/{pedido}/status', [ProveedorPedidoController::class, 'updateStatus'])
            ->name('proveedor.pedidos.update-status');

        // Preparar envío (compatibilidad)
        Route::patch('pedidos/{pedido}/prepare-shipment', [ProveedorPedidoController::class, 'prepareShipment'])
            ->name('proveedor.pedidos.prepare-shipment');

        // Confirmar entrega (compatibilidad)
        Route::patch('pedidos/{pedido}/confirm-delivery', [ProveedorPedidoController::class, 'confirmDelivery'])
            ->name('proveedor.pedidos.confirm-delivery');

        // Rechazar pedido (compatibilidad)
        Route::patch('pedidos/{pedido}/reject', [ProveedorPedidoController::class, 'rechazar'])
            ->name('proveedor.pedidos.reject');

        // Exportar pedidos del proveedor (compatibilidad)
        Route::post('pedidos/export', [ProveedorPedidoController::class, 'exportar'])
            ->name('proveedor.pedidos.export');
    });

});
