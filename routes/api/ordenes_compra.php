<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrdenCompraController;

/*
|--------------------------------------------------------------------------
| Rutas API para Gestión de Órdenes de Compra
|--------------------------------------------------------------------------
|
| Rutas principales para la gestión de órdenes de compra (OC).
| Incluye operaciones CRUD, consultas, estadísticas y conversiones.
|

Route::middleware(['auth:sanctum'])->group(function () {
    
    // Rutas principales de órdenes de compra
    Route::prefix('ordenes-compra')->group(function () {
        
        // Listado y consultas
        Route::get('/', [OrdenCompraController::class, 'index'])
            ->name('ordenes-compra.index');
        
        Route::get('/consultar', [OrdenCompraController::class, 'consultar'])
            ->name('ordenes-compra.consultar');
        
        // Estadísticas y reportes - COMENTADO: Se usan las rutas del dashboard específico del proveedor
        // Route::get('/estadisticas', [OrdenCompraController::class, 'estadisticas'])
        //     ->name('ordenes-compra.estadisticas');
        // 
        // Route::get('/alertas', [OrdenCompraController::class, 'alertas'])
        //     ->name('ordenes-compra.alertas');
        
        // Operaciones de creación y actualización
        Route::post('/', [OrdenCompraController::class, 'store'])
            ->name('ordenes-compra.store');
        
        Route::put('/{ordenCompra}', [OrdenCompraController::class, 'update'])
            ->name('ordenes-compra.update');
        
        // Consulta individual
        Route::get('/{ordenCompra}', [OrdenCompraController::class, 'show'])
            ->name('ordenes-compra.show');
        
        // Eliminar orden de compra
        Route::delete('/{ordenCompra}', [OrdenCompraController::class, 'destroy'])
            ->name('ordenes-compra.destroy');
        
        // Operaciones especiales
        Route::post('/{ordenCompra}/aprobar', [OrdenCompraController::class, 'aprobar'])
            ->name('ordenes-compra.aprobar');
        
        Route::post('/{ordenCompra}/rechazar', [OrdenCompraController::class, 'rechazar'])
            ->name('ordenes-compra.rechazar');
        
        Route::post('/{ordenCompra}/cancelar', [OrdenCompraController::class, 'cancelar'])
            ->name('ordenes-compra.cancelar');
        
        // Conversión a solicitud de pago
        Route::post('/{ordenCompra}/generar-solicitud-pago', [OrdenCompraController::class, 'generarSolicitudPago'])
            ->name('ordenes-compra.generar-solicitud-pago');
        
        // Detalles de la orden
        Route::prefix('/{ordenCompra}/detalles')->group(function () {
            Route::get('/', [OrdenCompraController::class, 'detalles'])
                ->name('ordenes-compra.detalles');
            
            Route::post('/', [OrdenCompraController::class, 'agregarDetalle'])
                ->name('ordenes-compra.detalles.store');
            
            Route::put('/{detalle}', [OrdenCompraController::class, 'actualizarDetalle'])
                ->name('ordenes-compra.detalles.update');
            
            Route::delete('/{detalle}', [OrdenCompraController::class, 'eliminarDetalle'])
                ->name('ordenes-compra.detalles.destroy');
        });
        
        // Solicitudes de pago relacionadas
        Route::get('/{ordenCompra}/solicitudes-pago', [OrdenCompraController::class, 'solicitudesPago'])
            ->name('ordenes-compra.solicitudes-pago');
    });
    
    // Rutas de consulta masiva y reportes
    Route::prefix('reportes/ordenes-compra')->group(function () {
        Route::get('/resumen-mensual', [OrdenCompraController::class, 'resumenMensual'])
            ->name('reportes.ordenes-compra.mensual');
        
        Route::get('/por-proveedor', [OrdenCompraController::class, 'reportePorProveedor'])
            ->name('reportes.ordenes-compra.proveedor');
        
        Route::get('/pendientes', [OrdenCompraController::class, 'ordenesComprarPendientes'])
            ->name('reportes.ordenes-compra.pendientes');
    });
});
*/