<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\ClienteDashboardController;
use App\Enums\UserRoleEnumerate;

/*
|--------------------------------------------------------------------------
| RUTAS ESPECÍFICAS PARA ROL: CLIENTE
|--------------------------------------------------------------------------
| Estas rutas solo son accesibles para usuarios con rol CLIENTE
*/

Route::middleware(['auth:sanctum', 'role:' . UserRoleEnumerate::CLIENTE->value])->prefix('cliente')->group(function () {

    /**
     * GESTIÓN DE PEDIDOS (CLIENTE)
     */
    Route::prefix('pedidos')->group(function () {
        // CRUD básico
        Route::get('/', [PedidoController::class, 'index'])->middleware(['audit']);
        Route::post('/', [PedidoController::class, 'store'])->middleware(['audit']);
        Route::get('{pedido}', [PedidoController::class, 'show'])->middleware(['audit']);

        // Acciones específicas del cliente
        Route::patch('{pedido}/status', [PedidoController::class, 'updateStatus'])
            ->middleware(['audit'])
            ->name('cliente.pedidos.update-status');

        Route::patch('{pedido}/cancel', [PedidoController::class, 'cancel'])
            ->middleware(['audit'])
            ->name('cliente.pedidos.cancel');

        Route::post('{pedido}/duplicate', [PedidoController::class, 'duplicar'])
            ->middleware(['audit'])
            ->name('cliente.pedidos.duplicate');

        Route::patch('{pedido}/confirm-reception', [PedidoController::class, 'confirmarRecepcion'])
            ->middleware(['audit'])
            ->name('cliente.pedidos.confirm-reception');

        // Reportes y estadísticas
        Route::get('estadisticas', [PedidoController::class, 'estadisticas'])
            ->middleware(['audit'])
            ->name('cliente.pedidos.estadisticas');

        Route::post('export', [PedidoController::class, 'exportar'])
            ->middleware(['audit'])
            ->name('cliente.pedidos.export');

        // Documentos
        Route::get('{pedido}/pdf', [PedidoController::class, 'generatePDF'])
            ->middleware(['audit'])
            ->name('cliente.pedidos.pdf');

        Route::get('{pedido}/receipt', [PedidoController::class, 'downloadReceipt'])
            ->middleware(['audit'])
            ->name('cliente.pedidos.receipt');
    });


    /**
     * DASHBOARD CLIENTE
     */
    Route::prefix('dashboard')->group(function () {
        Route::get('stats', [ClienteDashboardController::class, 'getStats'])->middleware(['audit']);
        Route::get('resumen-gastos', [ClienteDashboardController::class, 'getResumenGastos'])->middleware(['audit']);
    });

    /**
     * REPORTES CLIENTE
     */
    Route::prefix('reportes')->group(function () {
        Route::get('mis-pedidos', [PedidoController::class, 'myPedidosReport'])
            ->middleware(['audit'])
            ->name('cliente.reportes.mis-pedidos');
    });
});

/**
 * RUTAS GLOBALES DE PEDIDOS (ACCESIBLES PARA CLIENTES)
 * Estas rutas mantienen compatibilidad con el código existente
 */
Route::middleware(['auth:sanctum'])->group(function () {

    // Rutas principales de pedidos (compatibilidad)
    Route::apiResource('pedidos', PedidoController::class)->except(['update']);

    // Rutas específicas para pedidos (compatibilidad)
    Route::prefix('pedidos')->group(function () {
        Route::patch('{pedido}/status', [PedidoController::class, 'updateStatus'])
            ->name('pedidos.update-status');
        Route::patch('{pedido}/cancel', [PedidoController::class, 'cancel'])
            ->name('pedidos.cancel');
        Route::post('{pedido}/duplicate', [PedidoController::class, 'duplicar'])
            ->name('pedidos.duplicate');
        Route::patch('{pedido}/confirm-reception', [PedidoController::class, 'confirmarRecepcion'])
            ->name('pedidos.confirm-reception');
        Route::get('estadisticas', [PedidoController::class, 'estadisticas'])
            ->name('pedidos.estadisticas');
        Route::post('export', [PedidoController::class, 'exportar'])
            ->name('pedidos.export');
    });
});
