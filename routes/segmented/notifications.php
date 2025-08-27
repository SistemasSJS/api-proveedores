<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NotificationController;

// Rutas de autenticación de broadcasting
Broadcast::routes(['middleware' => ['auth:sanctum']]);

// Rutas de notificaciones
Route::middleware(['auth:sanctum'])->group(function () {
  Route::prefix('notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'getNotifications'])->middleware(['audit']);
    Route::post('/test', [NotificationController::class, 'sendTest'])->middleware(['audit']);
    Route::post('/send', [NotificationController::class, 'sendToCurrentUser'])->middleware(['audit']);
    Route::post('/send/{userId}', [NotificationController::class, 'sendToUser'])->middleware(['audit']);
    Route::patch('/{notificationId}/read', [NotificationController::class, 'markAsRead'])->middleware(['audit']);
    Route::patch('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->middleware(['audit']);
  });
});
