<?php

namespace App\Channels;

use App\Services\FcmService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Canal personalizado para enviar notificaciones push via FCM
 * Envío INSTANTÁNEO - Sin colas
 */
class FcmChannel
{
    protected FcmService $fcmService;
    
    public function __construct(FcmService $fcmService)
    {
        $this->fcmService = $fcmService;
    }
    
    /**
     * Enviar la notificación al usuario
     */
    public function send(object $notifiable, Notification $notification): void
    {
        // Verificar si el usuario tiene tokens FCM activos
        if (!method_exists($notifiable, 'deviceTokens')) {
            Log::warning('FCM Channel: El modelo no tiene relación deviceTokens', [
                'notifiable_type' => get_class($notifiable),
                'notifiable_id' => $notifiable->id ?? null,
            ]);
            return;
        }
        
        // Obtener tokens activos del usuario
        $tokens = $notifiable->deviceTokens()
            ->where('is_active', true)
            ->pluck('token')
            ->toArray();
        
        if (empty($tokens)) {
            Log::info('FCM Channel: Usuario sin tokens activos', [
                'user_id' => $notifiable->id ?? null,
            ]);
            return;
        }
        
        // Obtener el payload de la notificación
        if (!method_exists($notification, 'toFcm')) {
            Log::warning('FCM Channel: La notificación no implementa toFcm()', [
                'notification_type' => get_class($notification),
            ]);
            return;
        }
        
        $payload = $notification->toFcm($notifiable);
        
        // Extraer título, body y data
        $notificationData = [
            'title' => $payload['title'] ?? 'Notificación',
            'body' => $payload['body'] ?? '',
        ];
        
        $data = $payload['data'] ?? [];
        
        // Agregar configuración de Android si está presente
        if (isset($payload['android'])) {
            $notificationData['android'] = $payload['android'];
        }
        
        // Agregar configuración de iOS si está presente
        if (isset($payload['apns'])) {
            $notificationData['apns'] = $payload['apns'];
        }
        
        // Enviar notificación push
        try {
            $success = $this->fcmService->sendToTokens(
                $tokens,
                $notificationData,
                $data
            );
            
            if ($success) {
                Log::info('FCM Channel: Notificación enviada exitosamente', [
                    'user_id' => $notifiable->id ?? null,
                    'tokens_count' => count($tokens),
                    'notification_type' => get_class($notification),
                ]);
            } else {
                Log::warning('FCM Channel: Error al enviar notificación', [
                    'user_id' => $notifiable->id ?? null,
                    'tokens_count' => count($tokens),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('FCM Channel: Excepción al enviar notificación', [
                'user_id' => $notifiable->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
