<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\NotificacionController;

/*
|--------------------------------------------------------------------------
| RUTAS DE NOTIFICACIONES Y SERVICIOS ESPECIALIZADOS
|--------------------------------------------------------------------------
| Estas rutas manejan notificaciones y otros servicios específicos
*/

Route::middleware('auth:sanctum')->group(function () {

    /**
     * NOTIFICACIONES ESPECIALIZADAS DE PEDIDOS
     */
    Route::prefix('notifications')->group(function () {
        // Marcar notificación como leída
        Route::patch('pedidos/{pedido}/mark-read', [PedidoController::class, 'markNotificationRead'])
            ->middleware(['audit'])
            ->name('notifications.pedidos.mark-read');

        // Configurar alertas de pedidos
        Route::post('pedidos/alerts', [PedidoController::class, 'configureAlerts'])
            ->middleware(['audit'])
            ->name('notifications.pedidos.configure-alerts');
    });

});
