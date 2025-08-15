<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\ProveedorPedidoController;
use App\Enums\UserRoleEnumerate;

/*
|--------------------------------------------------------------------------
| RUTAS PARA ROLES MIXTOS
|--------------------------------------------------------------------------
| Estas rutas están disponibles para múltiples roles específicos
*/

/**
 * ROLES MIXTOS: GERENTE + ADMINISTRADOR + CLIENTE
 */
Route::middleware(['auth:sanctum', 'role:' . UserRoleEnumerate::CLIENTE->value . ',' . UserRoleEnumerate::GERENTE->value . ',' . UserRoleEnumerate::ADMINISTRADOR->value])->group(function () {
    
    /**
     * BÚSQUEDAS AVANZADAS
     */
    Route::prefix('busquedas')->group(function () {
        Route::post('pedidos', [PedidoController::class, 'advancedSearch'])
            ->middleware(['audit'])
            ->name('busquedas.pedidos.advanced-search');
            
        Route::get('pedidos/saved-filters', [PedidoController::class, 'savedFilters'])
            ->middleware(['audit'])
            ->name('busquedas.pedidos.saved-filters');
            
        Route::post('pedidos/save-filter', [PedidoController::class, 'saveFilter'])
            ->middleware(['audit'])
            ->name('busquedas.pedidos.save-filter');
    });

    /**
     * ANÁLISIS Y TENDENCIAS
     */
    Route::prefix('analytics')->group(function () {
        Route::get('pedidos-trends', [PedidoController::class, 'pedidosTrends'])
            ->middleware(['audit'])
            ->name('analytics.pedidos-trends');
            
        Route::get('performance-comparison', [PedidoController::class, 'performanceComparison'])
            ->middleware(['audit'])
            ->name('analytics.performance-comparison');
    });

    /**
     * DOCUMENTOS Y EXPORTACIONES
     */
    Route::prefix('documentos')->group(function () {
        Route::get('pedidos/{pedido}/shipping-label', [PedidoController::class, 'shippingLabel'])
            ->middleware(['audit'])
            ->name('documentos.pedidos.shipping-label');
            
        Route::get('pedidos/{pedido}/delivery-documents', [PedidoController::class, 'deliveryDocuments'])
            ->middleware(['audit'])
            ->name('documentos.pedidos.delivery-documents');
    });

});

/**
 * RUTAS DE BÚSQUEDA Y FILTRADO (COMPATIBILIDAD)
 */
Route::middleware(['auth:sanctum'])->group(function () {

    // Búsqueda avanzada de pedidos (compatibilidad)
    Route::post('pedidos/search', [PedidoController::class, 'advancedSearch'])
        ->name('pedidos.advanced-search');

    // Filtros guardados (compatibilidad)
    Route::get('pedidos/saved-filters', [PedidoController::class, 'savedFilters'])
        ->name('pedidos.saved-filters');

    Route::post('pedidos/save-filter', [PedidoController::class, 'saveFilter'])
        ->name('pedidos.save-filter');

    // Búsqueda de pedidos por proveedor (compatibilidad)
    Route::post('proveedores/{proveedor}/pedidos/search', [ProveedorPedidoController::class, 'advancedSearch'])
        ->name('proveedor.pedidos.advanced-search');

});

/**
 * RUTAS DE EXPORTACIÓN Y DOCUMENTOS (COMPATIBILIDAD)
 */
Route::middleware(['auth:sanctum'])->group(function () {

    // Generar PDF de pedido (compatibilidad)
    Route::get('pedidos/{pedido}/pdf', [PedidoController::class, 'generatePDF'])
        ->name('pedidos.pdf');

    // Descargar comprobante de pedido (compatibilidad)
    Route::get('pedidos/{pedido}/receipt', [PedidoController::class, 'downloadReceipt'])
        ->name('pedidos.receipt');

    // Generar etiqueta de envío (compatibilidad)
    Route::get('pedidos/{pedido}/shipping-label', [PedidoController::class, 'shippingLabel'])
        ->name('pedidos.shipping-label');

    // Documentos de entrega (compatibilidad)
    Route::get('pedidos/{pedido}/delivery-documents', [PedidoController::class, 'deliveryDocuments'])
        ->name('pedidos.delivery-documents');

});

/**
 * RUTAS DE CONSULTA Y REPORTING (COMPATIBILIDAD)
 */
Route::middleware(['auth:sanctum'])->group(function () {

    // Reportes de pedidos por cliente (compatibilidad)
    Route::get('reports/my-pedidos', [PedidoController::class, 'myPedidosReport'])
        ->name('reports.my-pedidos');

    // Reportes de pedidos por proveedor (compatibilidad)
    Route::get('reports/proveedores/{proveedor}/pedidos', [ProveedorPedidoController::class, 'proveedorPedidosReport'])
        ->name('reports.proveedor-pedidos');

    // Análisis de tendencias (compatibilidad)
    Route::get('analytics/pedidos-trends', [PedidoController::class, 'pedidosTrends'])
        ->name('analytics.pedidos-trends');

    // Comparativas de rendimiento (compatibilidad)
    Route::get('analytics/performance-comparison', [PedidoController::class, 'performanceComparison'])
        ->name('analytics.performance-comparison');

});
