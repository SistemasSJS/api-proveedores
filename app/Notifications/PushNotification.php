<?php

namespace App\Notifications;

use App\Models\TipoNotificacion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PushNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    public $title;
    public $message;
    public $type;
    public $data;
    protected ?TipoNotificacion $tipoNotificacion = null;

    /**
     * Create a new notification instance.
     */
    public function __construct($title, $message, $type = 'info', $data = [])
    {
        $this->title = $title;
        $this->message = $message;
        $this->type = $type;
        $this->data = $data;
        
        // Cargar el tipo de notificación por defecto para PushNotification
        $this->loadTipoNotificacion();
    }
    
    /**
     * Cargar el tipo de notificación MENSAJE_GENERAL como tipo por defecto
     */
    protected function loadTipoNotificacion(): void
    {
        // Cache del tipo de notificación por 1 hora
        $cacheKey = "tipo_notificacion_MENSAJE_GENERAL";
        
        $this->tipoNotificacion = Cache::remember($cacheKey, 3600, function () {
            return TipoNotificacion::where('codigo', 'MENSAJE_GENERAL')
                ->where('estatus', true)
                ->first();
        });

        if (!$this->tipoNotificacion) {
            Log::warning('Tipo de notificación MENSAJE_GENERAL no encontrado o inactivo');
        }
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
            'tipo_notificacion_id' => $this->tipoNotificacion?->id,
            'titulo' => $this->title,
            'mensaje' => $this->message,
            'icono' => $this->tipoNotificacion?->icono ?? 'notifications-outline',
            'color' => $this->tipoNotificacion?->color ?? 'primary',
            'type' => $this->type,
            'data' => $this->data,
            'timestamp' => now()->toIsoString(),
            'read_at' => null
        ];
    }

    /**
     * Get the type of the notification being broadcast.
     */
    public function broadcastType(): string
    {
        return 'notification';
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tipo_notificacion_id' => $this->tipoNotificacion?->id,
            'titulo' => $this->title,
            'mensaje' => $this->message,
            'icono' => $this->tipoNotificacion?->icono ?? 'notifications-outline',
            'color' => $this->tipoNotificacion?->color ?? 'primary',
            'type' => $this->type,
            'data' => $this->data,
            'timestamp' => now()->toIsoString()
        ];
    }
}
