<?php

use App\Enums\UserRoleEnumerate;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ImagenController;
use App\Http\Controllers\UnidadMedidaController;
use App\Http\Controllers\GrupoController;

// Rutas públicas
Route::post('register', [AuthController::class, 'register']); // Registro del proveedor
Route::post('login', [AuthController::class, 'login']);       // Login del proveedor


Route::apiResource('imagenes', ImagenController::class);
Route::apiResource('unidades-medida', UnidadMedidaController::class);
Route::apiResource('grupos', GrupoController::class);

// Rutas protegidas
Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('me', [AuthController::class, 'me']);  // Obtener datos del proveedor autenticado

    Route::middleware(['role:' . UserRoleEnumerate::ADMIM->value])->group(function () {
        // Rutas de Proveedores (RESTful)
        Route::apiResource('users', UserController::class);
    });


    Route::middleware('role:' . UserRoleEnumerate::ADMIM->value . ',' . UserRoleEnumerate::PROVEEDOR->value)->group(function () {

        // Rutas de Proveedores (RESTful)
        Route::apiResource('proveedores', ProveedorController::class);

        // Rutas de Sucursales
        Route::apiResource('sucursales', SucursalController::class);

        // Rutas de Productos
        Route::apiResource('productos', ProductoController::class);

        // Rutas adicionales del proveedor
        Route::prefix('proveedores/{id}')->group(function () {
            Route::get('productos', [ProveedorController::class, 'productosPorProveedor']);
            Route::get('sucursales', [ProveedorController::class, 'sucursalesPorProveedor']);
        });
    });
});
