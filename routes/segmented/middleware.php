<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\ProveedorPedidoController;

/*
|--------------------------------------------------------------------------
| MIDDLEWARE DE VERIFICACIÓN ESPECÍFICOS
|--------------------------------------------------------------------------
| Estas rutas requieren middleware especializados de verificación
*/

Route::middleware('auth:sanctum')->group(function () {

    /**
     * VERIFICACIÓN DE PROPIEDAD DE PEDIDOS
     */
    Route::middleware(['verify.pedido.owner'])->prefix('mis-pedidos')->group(function () {
        Route::get('{pedido}/detailed', [PedidoController::class, 'detailedView'])
            ->middleware(['audit'])
            ->name('mis-pedidos.detailed');
            
        Route::patch('{pedido}/update-preferences', [PedidoController::class, 'updatePreferences'])
            ->middleware(['audit'])
            ->name('mis-pedidos.update-preferences');
    });

    /**
     * VERIFICACIÓN DE ACCESO DE PROVEEDOR
     */
    Route::middleware(['verify.proveedor.access'])->prefix('proveedor-pedidos')->group(function () {
        Route::get('{pedido}/internal-notes', [ProveedorPedidoController::class, 'internalNotes'])
            ->middleware(['audit'])
            ->name('proveedor-pedidos.internal-notes');
            
        Route::post('{pedido}/add-note', [ProveedorPedidoController::class, 'addInternalNote'])
            ->middleware(['audit'])
            ->name('proveedor-pedidos.add-note');
    });

});
