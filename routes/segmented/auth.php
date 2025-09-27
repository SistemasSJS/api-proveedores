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
    Route::post('register', [AuthController::class, 'register'])->middleware(['audit']);
    Route::post('completar-registro', [AuthController::class, 'register_completar'])->middleware(['audit']);
    Route::post('register_proveedor', [AuthController::class, 'register_proveedor'])->middleware(['audit']);
    Route::post('register_proveedor_completar', [AuthController::class, 'register_proveedor_completar'])->middleware(['audit']);
    Route::post('register_basico', [AuthController::class, 'register_proveedor_basico_sp'])->middleware(['audit']);
    /**
     * PERFIL Y GESTIÓN DE CUENTA
     */
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me'])->middleware(['audit']);
        Route::post('refresh', [AuthController::class, 'refresh'])->middleware(['audit']);
        Route::post('update-img-perfil', [AuthController::class, 'update_foto_perfil'])->middleware(['audit']);
        Route::post('update-credentials', [AuthController::class, 'updateUser'])->middleware(['audit']);
        Route::get('logout', [AuthController::class, 'logout'])->middleware(['audit']);
    });
});
