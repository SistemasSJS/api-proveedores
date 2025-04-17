<?php

use App\Enums\UserRoleEnumerate;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\ProductoController;


// Rutas públicas
Route::post('register', [AuthController::class, 'register']); // Registro del proveedor
Route::post('login', [AuthController::class, 'login']);       // Login del proveedor


// Rutas protegidas
Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('me', [AuthController::class, 'me']);  // Obtener datos del proveedor autenticado

    Route::middleware(['role:' . UserRoleEnumerate::ADMIM->value])->group(function () {
        // Rutas de Proveedores
        Route::apiResource('proveedores', ProveedorController::class);

        // Rutas de Sucursales
        Route::apiResource('sucursales', SucursalController::class);

        // Rutas de Productos
        Route::apiResource('productos', ProductoController::class);
    });
});
