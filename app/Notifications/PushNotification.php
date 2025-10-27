<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class PushNotification extends Notification implements ShouldBroadcastNow
{
    public $title;
    public $message;
    public $type;
    public $data;

    // Para saber desde qué canal se envía
    protected $currentChannel = null;

    public function __construct($title, $message, $type = 'info', $data = [])
    {
        $this->title = $title;
        $this->message = $message;
        $this->type = $type;
        $this->data = $data;
    }

    /**
     * Canales de envío
     */
    public function via(object $notifiable): array
    {
        return ['broadcast', 'database', 'fcm'];
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
     */
    public function broadcastType(): string
    {
        return 'notification';
    }
}
