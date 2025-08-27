<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\SendNotificationRequest;
use App\Http\Resources\Notification\UserResource;
use App\Http\Resources\Notification\NotificationResource;
use App\Models\User;
use App\Notifications\PushNotification;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    use ApiResponse;

    /**
     * Enviar una notificación a un usuario específico
     */
    public function sendToUser(SendNotificationRequest $request, $userId)
    {
        $user = User::findOrFail($userId);

        try {
            $user->notify(new PushNotification(
                $request->input('title'),
                $request->input('message'),
                $request->input('type', 'info'),
                $request->input('data', [])
            ));

            return $this->success(
                new UserResource($user),
                'Notificación enviada exitosamente'
            );
        } catch (\Exception $e) {
            return $this->error(
                'Error al enviar la notificación',
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * Enviar una notificación al usuario autenticado
     */
    public function sendToCurrentUser(SendNotificationRequest $request)
    {
        $user = Auth::user();

        try {
            $user->notify(new PushNotification(
                $request->input('title'),
                $request->input('message'),
                $request->input('type', 'info'),
                $request->input('data', [])
            ));

            return $this->success(
                new UserResource($user),
                'Notificación enviada exitosamente'
            );
        } catch (\Exception $e) {
            return $this->error(
                'Error al enviar la notificación',
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * Enviar notificación de prueba
     */
    public function sendTest()
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
                    'test' => true
                ]
            ));

            return $this->success(
                new UserResource($user),
                'Notificación de prueba enviada'
            );
        } catch (\Exception $e) {
            return $this->error(
                'Error al enviar la notificación de prueba',
                $e->getMessage(),
                500
            );
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

        return $this->paginated(
            NotificationResource::collection($notifications)->resource,
            'Lista de notificaciones'
        );
    }

    /**
     * Marcar una notificación como leída
     */
    public function markAsRead($notificationId)
    {
        $user = Auth::user();

        $notification = $user->notifications()->find($notificationId);

        if (!$notification) {
            return $this->error('Notificación no encontrada', null, 404);
        }

        $notification->markAsRead();

        return $this->success(null, 'Notificación marcada como leída');
    }

    /**
     * Marcar todas las notificaciones como leídas
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        $user->unreadNotifications->markAsRead();

        return $this->success(null, 'Todas las notificaciones marcadas como leídas');
    }
}
