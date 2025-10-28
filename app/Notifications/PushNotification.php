<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Notifications\Notification;

class PushNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    public $title;
    public $message;
    public $type;
    public $data;

    public function __construct(string $title, string $message, string $type = 'info', array $data = [])
    {
        $this->title = $title;
        $this->message = $message;
        $this->type = $type;
        $this->data = $data;
    }

    public function via(object $notifiable): array
    {
        $channels = ['database', 'broadcast'];

        if (method_exists($notifiable, 'activeDeviceTokens') && $notifiable->activeDeviceTokens()->exists()) {
            $channels[] = 'fcm';
        }

        return $channels;
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->formatPayload());
    }

    public function toArray(object $notifiable): array
    {
        return $this->formatPayload();
    }

    public function toFcm(object $notifiable): array
    {
        $payload = $this->formatPayload();

        return [
            'notification' => [
                'title' => $payload['title'],
                'body' => $payload['mensaje'],
            ],
            'data' => array_merge($payload['data'], [
                'id' => $payload['id'],
                'type' => $payload['type'],
                'timestamp' => $payload['timestamp'],
            ]),
        ];
    }

    protected function formatPayload(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'mensaje' => $this->message,
            'type' => $this->type,
            'data' => $this->data,
            'timestamp' => now()->toIsoString(),
            'read_at' => null,
        ];
    }

    public function broadcastType(): string
    {
        return 'notification';
    }

    public function broadcastOn()
    {
        return [new Channel('public-notifications')];
    }
}
