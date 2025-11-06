<?php

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\PushNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Enviar una notificación a un usuario específico
     */
    public function sendToUser(Request $request, $userId)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'nullable|string|in:info,success,warning,error,danger',
            'data' => 'nullable|array',
        ]);

        $user = User::findOrFail($userId);

        try {
            $user->notify(new PushNotification(
                $request->input('title'),
                $request->input('message'),
                $request->input('type', 'info'),
                $request->input('data', [])
            ));

            return response()->json([
                'success' => true,
                'message' => 'Notificación enviada exitosamente',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar la notificación',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Enviar una notificación al usuario autenticado
     */
    public function sendToCurrentUser(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'nullable|string|in:info,success,warning,error,danger',
            'data' => 'nullable|array',
        ]);

        $user = Auth::user();

        try {
            $user->notify(new PushNotification(
                $request->input('title'),
                $request->input('message'),
                $request->input('type', 'info'),
                $request->input('data', [])
            ));

            return response()->json([
                'success' => true,
                'message' => 'Notificación enviada exitosamente',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar la notificación',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Enviar notificación de prueba
     */
    public function sendTest(Request $request)
    {
        $user = Auth::user();

        try {
            $user->notify(new PushNotification(
                '🔔 Notificación de Prueba',
                'Esta es una notificación de prueba desde la API. ¡El sistema de WebSocket está funcionando correctamente!',
                'success',
                [
                    'timestamp' => now()->toIsoString(),
                    'source' => 'api_test',
                    'test' => true,
                ]
            ));

            return response()->json([
                'success' => true,
                'message' => 'Notificación de prueba enviada',
                'info' => 'Revisa la aplicación, deberías ver la notificación en tiempo real',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar la notificación de prueba',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener las notificaciones del usuario autenticado
     */
    public function getNotifications(Request $request)
    {
        $user = Auth::user();

        $notifications = $user->notifications()
            ->latest()
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'notifications' => $notifications,
        ]);
    }

    /**
     * Marcar una notificación como leída
     */
    public function markAsRead($notificationId)
    {
        $user = Auth::user();

        $notification = $user->notifications()->find($notificationId);

        if (! $notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notificación no encontrada',
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notificación marcada como leída',
        ]);
    }

    /**
     * Marcar todas las notificaciones como leídas
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        $user->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Todas las notificaciones marcadas como leídas',
        ]);
    }

    /**
     * Polling endpoint para notificaciones
     * Usado por el servicio SSE/Polling del frontend
     */
    public function poll(Request $request)
    {
        $user = Auth::user();
        $lastTimestamp = $request->query('last_timestamp');

        $query = $user->notifications()->latest();

        if ($lastTimestamp) {
            $query->where('created_at', '>', $lastTimestamp);
        }

        $notifications = $query->take(50)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'has_changes' => $notifications->count() > 0,
                'notifications' => $notifications,
                'timestamp' => now()->toIsoString(),
                'unread_count' => $user->unreadNotifications->count()
            ]
        ]);
    }
}
