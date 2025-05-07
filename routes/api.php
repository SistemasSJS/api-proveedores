<?php

use App\Enums\UserRoleEnumerate;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ImagenController;
use App\Http\Controllers\UnidadMedidaController;
use App\Http\Controllers\GrupoController;
use App\Http\Controllers\LineaController;
use App\Http\Controllers\MarcaController;
use App\Models\Marca;

/**
 * Rutas Publicas. Que no necesitan procteccion
 * FIXME: Config Access Api Token on all routes, and settings CORDS. 
 * TODO: Verificar configuracion de origen para las petiones.  
 */
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::get('categorias', [CategoriaController::class, 'index']);
Route::get('lineas', [LineaController::class, 'index']);
Route::get('marcas', [MarcaController::class, 'index']);
Route::get('unidades-medida', [UnidadMedidaController::class, 'index']);
Route::get('grupos', [GrupoController::class, 'index']);


Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('me', [AuthController::class, 'me']);  // Obtener datos del proveedor autenticado

    Route::middleware([
        'role:'
            . UserRoleEnumerate::SUPER_ADMIN->value . ','
            . UserRoleEnumerate::PROVEEDOR->value . ','
            . UserRoleEnumerate::ADMIN->value
    ])->group(function () {
        Route::apiResource('proveedores', ProveedorController::class);
        Route::prefix('proveedores/{id}')->group(function () {
            Route::get('productos', [ProveedorController::class, 'productosPorProveedor']);
            Route::get('sucursales', [ProveedorController::class, 'sucursalesPorProveedor']);
        });
    });


    Route::middleware(
        'role:'
            . UserRoleEnumerate::SUPER_ADMIN->value . ','
            . UserRoleEnumerate::ADMIN->value
    )->group(function () {

        /**
         * Rutas para los CRUDS. Only admins
         */
        Route::apiResource('users', UserController::class);
        Route::apiResource('proveedores', ProveedorController::class);
        Route::apiResource('sucursales', SucursalController::class);
        Route::apiResource('productos', ProductoController::class);
        Route::apiResource('imagenes', ImagenController::class);
        Route::apiResource('unidades-medida', UnidadMedidaController::class);
        Route::apiResource('grupos', GrupoController::class);
        Route::apiResource('categorias', CategoriaController::class);
        Route::apiResource('lineas', LineaController::class);
        Route::apiResource('marcas', MarcaController::class);
    });
});
