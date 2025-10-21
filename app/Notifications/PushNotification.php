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

    /**
     * Create a new notification instance.
     */
    public function __construct($title, $message, $type = 'info', $data = [])
    {
        $this->title = $title;
        $this->message = $message;
        $this->type = $type;
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['broadcast', 'database'];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): array
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

    /**
     * Get the type of the notification being broadcast.
     */
    public function broadcastType(): string
    {
        return 'notifications';
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'mensaje' => $this->message,
            'type' => $this->type,
            'data' => $this->data,
            'timestamp' => now()->toIsoString(),
        ];
    }
}
