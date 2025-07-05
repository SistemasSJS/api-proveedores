<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TiendaController;
use App\Http\Controllers\ProductoBusquedaController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductoController;

/*
|--------------------------------------------------------------------------
| RECURSOS COMPARTIDOS (TODOS LOS ROLES AUTENTICADOS)
|--------------------------------------------------------------------------
| Estas rutas están disponibles para todos los usuarios autenticados
*/

Route::middleware('auth:sanctum')->group(function () {

    /**
     * TIENDA ONLINE
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
            Route::get('{producto}', [TiendaController::class, 'show'])->middleware(['audit']);
        });
    });

    /**
     * BÚSQUEDA Y DISPONIBILIDAD DE PRODUCTOS
     */
    Route::prefix('productos')->group(function () {
        Route::get('buscar', [ProductoBusquedaController::class, 'buscar'])->middleware(['audit']);
        Route::get('{producto}/disponibilidad', [ProductoBusquedaController::class, 'verificarDisponibilidad'])->middleware(['audit']);
    });

    /**
     * NOTIFICACIONES
     */
    Route::prefix('notificaciones')->group(function () {
        Route::get('/', [NotificacionController::class, 'index'])->middleware(['audit']);
        Route::patch('{notificacion}/leer', [NotificacionController::class, 'marcarComoLeida'])->middleware(['audit']);
        Route::patch('marcar-todas-leidas', [NotificacionController::class, 'marcarTodasComoLeidas'])->middleware(['audit']);
        Route::delete('{notificacion}', [NotificacionController::class, 'destroy'])->middleware(['audit']);
    });

    /**
     * DASHBOARD BÁSICO
     */
    Route::get('dashboard/stats', [DashboardController::class, 'getStats'])->middleware(['audit']);

});
