<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PedidoController;
use App\Events\TestEvent;

/*
|--------------------------------------------------------------------------
| RUTAS DE DESARROLLO Y TESTING
|--------------------------------------------------------------------------
| Estas rutas solo están disponibles en ambiente de desarrollo
*/

if (app()->environment('local', 'testing')) {
    Route::prefix('test/pedidos')->group(function () {

        // Generar pedidos de prueba
        Route::post('generate-test-data', [PedidoController::class, 'generateTestData'])
            ->name('test.pedidos.generate');

        // Simular webhook de transportista
        Route::post('simulate-tracking', [PedidoController::class, 'simulateTracking'])
            ->name('test.pedidos.simulate-tracking');

        // Benchmark de rendimiento
        Route::get('performance', [PedidoController::class, 'performanceTest'])
            ->name('test.pedidos.performance');
    });

    // Test de Reverb Broadcasting
    Route::get('test/reverb', function () {
        broadcast(new TestEvent('Hola desde Reverb! ' . now()));
        return response()->json([
            'message' => 'Evento TestEvent enviado al canal notifications',
            'timestamp' => now(),
        ]);
    })->name('test.reverb');
}
