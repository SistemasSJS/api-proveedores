<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TipoEmpresaController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProductoImagenController;
use App\Http\Controllers\UnidadMedidaController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\MarcaController;
use App\Http\Controllers\PedidoController;
use App\Notifications\PushNotification;
use App\Models\User;
use Illuminate\Support\Facades\Request;

/*
|--------------------------------------------------------------------------
| RUTAS PÚBLICAS
|--------------------------------------------------------------------------
| Estas rutas no requieren autenticación
*/


Route::get('status', function (Request $request) {
    // $userId = $request->query('id'); // o $request->get('id')
    // $user = User::find($userId);


    // if (!$user) {
    //     return response()->json([
    //         'status' => 'error',
    //         'message' => 'Usuario no encontrado',
    //     ], 404);
    // }

    // // Crear la notificación CON el canal push
    // $notification = new PushNotification(
    //     'Título de prueba',
    //     'Este es un mensaje de prueba',
    //     'info',
    //     [
    //         'channel' => 'push',  // ← AGREGAR ESTO
    //         'extra' => 'datos opcionales',
    //         // Opcional: agregar deep-link
    //         'type' => 'product',
    //         'entityId' => '123',
    //         'action' => 'view'
    //     ]
    // );

    // // Enviar la notificación
    // $user->notify($notification);

    return response()->json([
        'status' => 'ok',
        'message' => 'Notificación push enviada al usuario 14',
        // 'user' => [
        //     'id' => $user->id,
        //     'name' => $user->name
        // ]
    ]);
});

/**
 * CATÁLOGOS PÚBLICOS
 */
Route::get('roles-index', [RoleController::class, 'index']);
Route::get('tipos-empresa-index', [TipoEmpresaController::class, 'index']);

// Catálogos generales
Route::get('proveedores', [ProveedorController::class, 'index'])->middleware(['audit']);
Route::get('sucursales', [SucursalController::class, 'index'])->middleware(['audit']);
Route::get('productos', [ProductoController::class, 'index'])->middleware(['audit']);
Route::get('imagenes', [ProductoImagenController::class, 'index'])->middleware(['audit']);
Route::get('unidades-medida', [UnidadMedidaController::class, 'index'])->middleware(['audit']);
Route::get('categorias', [CategoriaController::class, 'index'])->middleware(['audit']);
Route::get('marcas', [MarcaController::class, 'index'])->middleware(['audit']);
Route::get('tipos-empresa', [TipoEmpresaController::class, 'index'])->middleware(['audit']);

/**
 * CONSULTAS PÚBLICAS ESPECIALIZADAS
 */
Route::middleware(['throttle:60,1'])->group(function () {
    // Webhook para actualizaciones de tracking (transportistas)
    Route::post('pedidos/{pedido}/tracking-update', [PedidoController::class, 'trackingUpdate'])
        ->name('pedidos.tracking-update');

    // Consulta pública de estado de pedido (con token)
    Route::get('pedidos/{pedido}/status/{token}', [PedidoController::class, 'publicStatus'])
        ->name('pedidos.public-status');
});
