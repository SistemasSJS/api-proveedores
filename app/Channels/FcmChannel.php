<?php

namespace App\Channels;

use App\Services\FcmService;
use Exception;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Canal de notificación personalizado para Firebase Cloud Messaging
 */
class FcmChannel
{
    protected FcmService $fcmService;

    public function __construct(FcmService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    /**
     * Enviar la notificación dada.
     *
     * @param  mixed  $notifiable
     */
    public function send($notifiable, Notification $notification): void
    {
        try {
            // Verificar que el método toFcm exista
            if (! method_exists($notification, 'toFcm')) {
                Log::warning('FCM Channel: Notificación no tiene método toFcm', [
                    'notification_class' => get_class($notification),
                    'notifiable_id' => $notifiable->id ?? 'unknown',
                ]);

                return;
            }

            // Obtener los tokens FCM activos del usuario
            $tokens = $notifiable->fcm_tokens ?? [];

            if (empty($tokens)) {
                Log::info('FCM Channel: No hay tokens activos para el usuario', [
                    'notifiable_id' => $notifiable->id ?? 'unknown',
                    'notifiable_class' => get_class($notifiable),
                ]);

                return;
            }

            // Obtener los datos de la notificación
            $fcmData = $notification->toFcm($notifiable);

            if (! $this->validateFcmData($fcmData)) {
                Log::error('FCM Channel: Datos de notificación inválidos', [
                    'notification_class' => get_class($notification),
                    'fcm_data' => $fcmData,
                ]);

                return;
            }

            // Extraer notificación y datos
            $notificationData = $fcmData['notification'] ?? [];
            $customData = $fcmData['data'] ?? [];

            // Enviar la notificación
            $success = $this->fcmService->sendToTokens($tokens, $notificationData, $customData);

            if ($success) {
                Log::info('FCM Channel: Notificación enviada correctamente', [
                    'notification_class' => get_class($notification),
                    'tokens_count' => count($tokens),
                    'notifiable_id' => $notifiable->id ?? 'unknown',
                ]);
            } else {
                Log::warning('FCM Channel: Falló el envío de notificación', [
                    'notification_class' => get_class($notification),
                    'tokens_count' => count($tokens),
                    'notifiable_id' => $notifiable->id ?? 'unknown',
                ]);
            }

        } catch (Exception $e) {
            Log::error('FCM Channel: Excepción enviando notificación', [
                'message' => $e->getMessage(),
                'notification_class' => get_class($notification),
                'notifiable_id' => $notifiable->id ?? 'unknown',
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Validar que los datos FCM tengan la estructura correcta
     */
    private function validateFcmData(array $fcmData): bool
    {
        // Debe tener al menos notification o data
        if (! isset($fcmData['notification']) && ! isset($fcmData['data'])) {
            return false;
        }

        // Si tiene notification, debe tener title o body
        if (isset($fcmData['notification'])) {
            $notification = $fcmData['notification'];
            if (! isset($notification['title']) && ! isset($notification['body'])) {
                return false;
            }
        }

        return true;
    }
}
