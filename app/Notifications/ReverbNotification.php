<?php

namespace App\Notifications;

use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ReverbNotification extends Notification implements ShouldBroadcastNow
{
    public function via(object $notifiable): array
    {
        return ['mail', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Notificación de prueba Reverb')
            ->line('Esto es una notificación de prueba enviada por Reverb.')
            ->action('Ver más', url('/'))
            ->line('Gracias por usar nuestra aplicación.');
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'titulo' => 'Notificación Reverb',
            'mensaje' => 'Esta es una notificación instantánea enviada vía Reverb.',
            'timestamp' => now()->toIsoString(),
        ]);
    }

    public function broadcastType(): string
    {
        return 'reverb-notification';
    }

    public function broadcastOn()
    {
        return [new Channel('public-notifications')];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'titulo' => 'Notificación Reverb',
            'mensaje' => 'Esta es una notificación instantánea enviada vía Reverb.',
            'timestamp' => now()->toIsoString(),
        ];
    }
}
