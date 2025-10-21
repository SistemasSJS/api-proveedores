<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StatusController;

/*
|--------------------------------------------------------------------------
| Status API Routes
|--------------------------------------------------------------------------
|
| Rutas para notificar usuarios desde endpoints externos
|
*/

// Ruta principal - notificación simple con query parameters
Route::get('/status', [StatusController::class, 'simpleNotify']);
Route::post('/status', [StatusController::class, 'notifyUser']);

// Rutas específicas
Route::get('/status/users', [StatusController::class, 'getUsers']);
Route::get('/status/system', [StatusController::class, 'systemStatus']);

// Alias para compatibilidad
Route::get('/notify', [StatusController::class, 'simpleNotify']);
Route::post('/notify', [StatusController::class, 'notifyUser']);
