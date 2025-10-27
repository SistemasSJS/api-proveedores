<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PedidoController;
use App\Events\TestEvent;
use App\Notifications\PushNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| RUTAS DE DESARROLLO Y TESTING
|--------------------------------------------------------------------------
| Estas rutas solo están disponibles en ambiente de desarrollo
*/

// if (app()->environment('local', 'testing')) {
// Test de Reverb Broadcasting
Route::get('test/reverb', function () {
    broadcast(new TestEvent('Hola desde Reverb! ' . now()));
    return response()->json([
        'message' => 'Evento TestEvent enviado al canal notifications',
        'timestamp' => now(),
    ]);
})->name('test.reverb');

// Test de Notificación Universal (POST)
Route::post('test/notification', function (Request $request) {
    $validated = $request->validate([
        'type' => 'required|string',
        'channel' => 'required|string|in:database,reverb,sse,polling,push',
        'title' => 'nullable|string',
        'message' => 'required|string',
        'data' => 'nullable|array'
    ]);

    $user = Auth::user();
    if (!$user) {
        return response()->json(['error' => 'Usuario no autenticado'], 401);
    }

    $title = $validated['title'] ?? 'Test Notification';
    $message = $validated['message'];
    $type = $validated['type'];
    $data = array_merge(
        $validated['data'] ?? [],
        [
            'test' => true,
            'channel' => $validated['channel'],
            'timestamp' => now()->toIsoString()
        ]
    );

    try {
        // Enviar notificación usando el sistema de Laravel
        $user->notifyNow(new PushNotification($title, $message, $type, $data));

        // Para canal reverb, también hacer broadcast adicional
        if ($validated['channel'] === 'reverb') {
            broadcast(new TestEvent([
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => $data,
                'timestamp' => now(),
                'user_id' => $user->id
            ]));
        }

        return response()->json([
            'success' => true,
            'message' => 'Notificación de prueba enviada',
            'type' => $type,
            'channel' => $validated['channel'],
            'timestamp' => now(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al enviar notificación',
            'error' => $e->getMessage()
        ], 500);
    }
})->middleware('auth:sanctum')->name('test.notification');

// Tests rápidos por tipo de notificación
Route::get('test/notification/type/{type}', function (string $type) {
    $user = Auth::user();
    if (!$user) {
        return response()->json(['error' => 'Usuario no autenticado'], 401);
    }

    $titles = [
        'info' => 'Información',
        'success' => 'Éxito',
        'warning' => 'Advertencia',
        'error' => 'Error',
        'pedido_nuevo' => 'Nuevo Pedido',
        'pedido_actualizado' => 'Pedido Actualizado'
    ];

    $messages = [
        'info' => 'Esta es una notificación informativa de prueba',
        'success' => 'Operación completada exitosamente',
        'warning' => 'Esta es una advertencia de prueba',
        'error' => 'Ha ocurrido un error de prueba',
        'pedido_nuevo' => 'Se ha recibido un nuevo pedido #TEST',
        'pedido_actualizado' => 'El pedido #TEST ha sido actualizado'
    ];

    try {
        $user->notifyNow(new PushNotification(
            $titles[$type] ?? 'Test',
            $messages[$type] ?? 'Mensaje de prueba',
            $type,
            ['test' => true, 'type_test' => $type]
        ));

        return response()->json([
            'success' => true,
            'message' => "Notificación de tipo '{$type}' enviada",
            'type' => $type,
            'timestamp' => now()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al enviar notificación',
            'error' => $e->getMessage()
        ], 500);
    }
})->middleware('auth:sanctum')->name('test.notification.type');

// Tests rápidos por canal
Route::get('test/notification/channel/{channel}', function (string $channel) {
    $user = Auth::user();
    if (!$user) {
        return response()->json(['error' => 'Usuario no autenticado'], 401);
    }

    $title = "Test Canal: {$channel}";
    $message = "Notificación de prueba enviada por canal {$channel}";
    $data = ['test' => true, 'channel_test' => $channel];

    try {
        $user->notifyNow(new PushNotification($title, $message, 'info', $data));

        if ($channel === 'reverb') {
            broadcast(new TestEvent([
                'type' => 'info',
                'title' => $title,
                'message' => $message,
                'data' => $data,
                'timestamp' => now(),
                'user_id' => $user->id
            ]));
        }

        return response()->json([
            'success' => true,
            'message' => "Notificación enviada por canal '{$channel}'",
            'channel' => $channel,
            'timestamp' => now()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al enviar notificación',
            'error' => $e->getMessage()
        ], 500);
    }
})->middleware('auth:sanctum')->name('test.notification.channel');

// Obtener lista de usuarios
Route::get('test/users', function () {
    $users = \App\Models\User::select('id', 'name', 'email')
        ->limit(100)
        ->get();

    return response()->json([
        'success' => true,
        'users' => $users
    ]);
})->middleware('auth:sanctum')->name('test.users');

// Enviar notificación a usuario específico con broadcast channel
Route::post('test/send-to-user', function (Request $request) {
    $validated = $request->validate([
        'user_id' => 'required|integer',
        'type' => 'required|string',
        'channel' => 'required|string|in:public,private,proveedor,push',
        'title' => 'required|string',
        'message' => 'required|string',
        'proveedor_id' => 'nullable|integer'
    ]);

    $targetUser = \App\Models\User::findOrFail($validated['user_id']);

    try {
        $data = [
            'test' => true,
            'channel' => $validated['channel'],
            'timestamp' => now()->toIsoString()
        ];

        // Siempre guardar en database
        $targetUser->notifyNow(new PushNotification(
            $validated['title'],
            $validated['message'],
            $validated['type'],
            $data
        ));

        // Broadcast según el canal seleccionado
        $broadcastData = [
            'type' => $validated['type'],
            'title' => $validated['title'],
            'message' => $validated['message'],
            'data' => $data,
            'user_id' => $targetUser->id,
            'timestamp' => now()
        ];

        switch ($validated['channel']) {
            case 'public':
                // Broadcast a canal público
                broadcast(new TestEvent($broadcastData))->toOthers();
                $channelInfo = 'public-notifications';
                break;

            case 'private':
                // Broadcast a canal privado del usuario
                broadcast(new \App\Events\NotificationSent($broadcastData))
                    ->toOthers();
                $channelInfo = "App.Models.User.{$targetUser->id}";
                break;

            case 'proveedor':
                // Broadcast a canal de proveedor
                if ($validated['proveedor_id']) {
                    $broadcastData['proveedor_id'] = $validated['proveedor_id'];
                    broadcast(new TestEvent($broadcastData))
                        ->toOthers();
                    $channelInfo = "proveedor.{$validated['proveedor_id']}";
                } else {
                    $channelInfo = 'proveedor (sin ID)';
                }
                break;

            case 'push':
                // Solo database + push, no broadcast
                $channelInfo = 'push-only (database)';
                break;
        }

        return response()->json([
            'success' => true,
            'message' => 'Notificación enviada exitosamente',
            'target_user' => [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
                'email' => $targetUser->email
            ],
            'channel' => $validated['channel'],
            'broadcast_channel' => $channelInfo ?? 'none',
            'timestamp' => now()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al enviar notificación',
            'error' => $e->getMessage()
        ], 500);
    }
})->middleware('auth:sanctum')->name('test.send-to-user');
// }
