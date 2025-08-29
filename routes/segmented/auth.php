<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| RUTAS DE AUTENTICACIÓN PROTEGIDAS
|--------------------------------------------------------------------------
| Estas rutas requieren autenticación con Sanctum
*/

Route::prefix('auth')->group(function () {


    /**
     * AUTENTICACION Y REGISTRO
     */
    Route::post('login', [AuthController::class, 'login'])->middleware(['audit']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('completar-registro', [AuthController::class, 'register_completar']);
    Route::post('register_proveedor', [AuthController::class, 'register_proveedor']);
    Route::post('register_proveedor_completar', [AuthController::class, 'register_proveedor_completar']);
    /**
     * PERFIL Y GESTIÓN DE CUENTA
     */
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('update-img-perfil', [AuthController::class, 'update_foto_perfil']);
        Route::put('update-usuario', [AuthController::class, 'updateUser']);
        Route::get('logout', [AuthController::class, 'logout']);
    });
});
