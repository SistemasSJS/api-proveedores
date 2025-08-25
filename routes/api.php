<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\NotificationController;

// Rutas de autenticación de broadcasting
Broadcast::routes(['middleware' => ['auth:sanctum']]);

// Rutas de notificaciones
Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'getNotifications']);
        Route::post('/test', [NotificationController::class, 'sendTest']);
        Route::post('/send', [NotificationController::class, 'sendToCurrentUser']);
        Route::post('/send/{userId}', [NotificationController::class, 'sendToUser']);
        Route::patch('/{notificationId}/read', [NotificationController::class, 'markAsRead']);
        Route::patch('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    });
});

require __DIR__ . '/segmented/routes-segmented.php';
