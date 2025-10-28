<?php

namespace App\Notifications;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Notificación multicanal que soporta:
 * - Reverb (broadcast) para usuarios web
 * - FCM para aplicaciones nativas (Android/iOS)
 * - WhatsApp (opcional)
 * - Database para historial
 */
class PushNotification extends Notification implements ShouldBroadcastNow, ShouldQueue
{
    use Queueable;

    public $title;
    public $message;
    public $type;
    public $data;

    // Para saber desde qué canal se envía
    protected $currentChannel = null;

    public function __construct(
        string $title,
        string $message,
        string $type = 'info',
        array $data = []
    ) {
        $this->title = $title;
        $this->message = $message;
        $this->type = $type;
        $this->data = $data;
    }

    /**
     * Canales de envío basados en contexto del usuario
     * - broadcast: Reverb WebSocket (para web)
     * - fcm: Push nativas (Android/iOS)
     * - database: Historial de notificaciones
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        // 1. SIEMPRE: Guardar en base de datos para historial
        $channels[] = 'database';

        // 2. SIEMPRE: Broadcast via Reverb (para usuarios web conectados)
        $channels[] = 'broadcast';

        // 3. CONDICIONAL: FCM para usuarios con tokens activos (nativos)
        if (
            method_exists($notifiable, 'activeDeviceTokens') &&
            $notifiable->activeDeviceTokens()->exists()
        ) {
            $channels[] = 'fcm';
        }

        return $channels;
    }

    /**
     * Payload para broadcasting
     */
    public function toBroadcast(object $notifiable): array
    {
        $this->currentChannel = 'broadcast';
        return $this->formatPayload();
    }

    /**
     * Payload para base de datos
     */
    public function toArray(object $notifiable): array
    {
        $this->currentChannel = 'database';
        return $this->formatPayload();
    }

    /**
     * Payload para FCM
     */
    public function toFcm(object $notifiable): array
    {
        $this->currentChannel = 'fcm';
        $payload = $this->formatPayload();

        return [
            'notification' => [
                'title' => $payload['title'],
                'body' => $payload['mensaje'],
            ],
            'data' => array_merge(
                $payload['data'],
                [
                    'id' => $payload['id'],
                    'type' => $payload['type'],
                    'timestamp' => $payload['timestamp'],
                ]
            ),
        ];
    }

    /**
     * Formato común del payload
     */
    protected function formatPayload(): array
    {
        $data = $this->data;

        // Solo para FCM
        if ($this->currentChannel === 'fcm') {
            $data['show_web_notification'] = true;
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'mensaje' => $this->message,
            'type' => $this->type,
            'data' => $data,
            'timestamp' => now()->toIsoString(),
            'read_at' => null,
        ];
    }

    /**
     * Tipo de broadcast
     * Laravel automáticamente transmite a: private-App.Models.User.{user_id}
     * El frontend escucha en: private-App.Models.User.{user_id}
     * Evento: Illuminate\Notifications\Events\BroadcastNotificationCreated
     */
    public function broadcastType(): string
    {
        return 'notification';
    }
}
