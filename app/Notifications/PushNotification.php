<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Notification;

class PushNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    public $title;
    public $message;
    public $type;
    public $data;

    // Nueva propiedad para saber desde qué canal se envía
    protected $currentChannel = null;

    public function __construct($title, $message, $type = 'info', $data = [])
    {
        $this->title = $title;
        $this->message = $message;
        $this->type = $type;
        $this->data = $data;
    }

    public function via(object $notifiable): array
    {
        return ['broadcast', 'database', 'fcm'];
    }

    public function toBroadcast(object $notifiable): array
    {
        $this->currentChannel = 'broadcast';
        return $this->formatPayload();
    }

    public function toArray(object $notifiable): array
    {
        $this->currentChannel = 'database';
        return $this->formatPayload();
    }

    public function toFcm(object $notifiable): array
    {
        $this->currentChannel = 'fcm';
        $payload = $this->formatPayload();
        
        // FCM requiere formato específico: notification + data
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

    protected function formatPayload(): array
    {
        $data = $this->data;

        // Si el canal actual es FCM → agregar bandera
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

    public function broadcastType(): string
    {
        return 'notification';
    }
}
