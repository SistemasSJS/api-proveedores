<?php

use App\Http\Controllers\CategoriaController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TiendaController;
use App\Http\Controllers\ProductoBusquedaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MarcaController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProductoImagenController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\TipoEmpresaController;
use App\Http\Controllers\UnidadMedidaController;

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

        /**
         * CATALOGOS PARA LOS FILTROS
         */
        Route::get('proveedores', [ProveedorController::class, 'index'])->middleware(['audit']);
        Route::get('sucursales', [SucursalController::class, 'index'])->middleware(['audit']);
        Route::get('productos', [ProductoController::class, 'index'])->middleware(['audit']);
        Route::get('imagenes', [ProductoImagenController::class, 'index'])->middleware(['audit']);
        Route::get('unidades-medida', [UnidadMedidaController::class, 'index'])->middleware(['audit']);
        Route::get('categorias', [CategoriaController::class, 'index'])->middleware(['audit']);
        Route::get('marcas', [MarcaController::class, 'index'])->middleware(['audit']);
        Route::get('tipos-empresa', [TipoEmpresaController::class, 'index'])->middleware(['audit']);

        /**
         * ACCESSO DIRECTOS
         */
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
     * DASHBOARD BÁSICO
     */
    Route::get('dashboard/stats', [DashboardController::class, 'getStats'])->middleware(['audit']);

    /**
     * DEVICE TOKENS - PUSH NOTIFICATIONS
     * Gestión de tokens FCM para notificaciones push
     */
    Route::prefix('device-tokens')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\DeviceTokenController::class, 'index'])->middleware(['audit']);
        Route::post('/', [\App\Http\Controllers\Api\DeviceTokenController::class, 'store'])->middleware(['audit']);
        Route::delete('{tokenId}/deactivate', [\App\Http\Controllers\Api\DeviceTokenController::class, 'deactivate'])->middleware(['audit']);
        Route::delete('cleanup', [\App\Http\Controllers\Api\DeviceTokenController::class, 'cleanup'])->middleware(['audit']);
        Route::post('test-notification', [\App\Http\Controllers\Api\DeviceTokenController::class, 'testNotification'])->middleware(['audit']);
    });

});
