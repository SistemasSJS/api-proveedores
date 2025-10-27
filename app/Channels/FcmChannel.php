<?php

namespace App\Channels;

use App\Services\FcmService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Canal personalizado para enviar notificaciones push mediante Firebase Cloud Messaging
 */
class FcmChannel
{
    protected FcmService $fcmService;

    public function __construct(FcmService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    /**
     * Enviar la notificación mediante FCM
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification): void
    {
        // Verificar que la notificación tenga el método toFcm
        if (!method_exists($notification, 'toFcm')) {
            Log::warning('FCM Channel: La notificación no tiene método toFcm()', [
                'notification' => get_class($notification),
            ]);
            return;
        }

        try {
            // Obtener tokens activos del usuario
            $tokens = $notifiable->fcm_tokens;

            if (empty($tokens)) {
                Log::info('FCM Channel: Usuario sin tokens activos', [
                    'user_id' => $notifiable->id,
                ]);
                return;
            }

            // Obtener el payload de la notificación
            $payload = $notification->toFcm($notifiable);

            if (!isset($payload['notification']) || !isset($payload['data'])) {
                Log::error('FCM Channel: Payload inválido', [
                    'payload' => $payload,
                ]);
                return;
            }

            // Enviar la notificación
            $success = $this->fcmService->sendToTokens(
                $tokens,
                $payload['notification'],
                $payload['data']
            );

            if ($success) {
                Log::info('FCM Channel: Notificación enviada exitosamente', [
                    'user_id' => $notifiable->id,
                    'tokens_count' => count($tokens),
                    'notification' => get_class($notification),
                ]);
            } else {
                Log::warning('FCM Channel: Fallo al enviar notificación', [
                    'user_id' => $notifiable->id,
                    'tokens_count' => count($tokens),
                ]);
            }

        } catch (Exception $e) {
            Log::error('FCM Channel: Error enviando notificación', [
                'user_id' => $notifiable->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
