<?php

namespace App\Notifications;

use App\Models\TipoNotificacion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Tipo de notificación asociado
     */
    protected ?TipoNotificacion $tipoNotificacion = null;

    /**
     * Datos específicos de la notificación
     */
    protected array $notificationData = [];

    /**
     * ID de la entidad relacionada (opcional)
     */
    protected ?int $entityId = null;

    /**
     * URL personalizada (opcional, sobrescribe la del tipo)
     */
    protected ?string $customUrl = null;

    /**
     * Constructor base
     */
    public function __construct(array $data = [], ?int $entityId = null, ?string $customUrl = null)
    {
        $this->notificationData = $data;
        $this->entityId = $entityId;
        $this->customUrl = $customUrl;
        
        // Cargar el tipo de notificación
        $this->loadTipoNotificacion();
    }

    /**
     * Método abstracto que debe implementar cada notificación concreta
     * para especificar su código de tipo
     */
    abstract protected function getCodigoTipo(): string;

    /**
     * Método abstracto para obtener el título de la notificación
     */
    abstract protected function getTitulo(): string;

    /**
     * Método abstracto para obtener el mensaje de la notificación
     */
    abstract protected function getMensaje(): string;

    /**
     * Cargar el tipo de notificación desde la base de datos
     */
    protected function loadTipoNotificacion(): void
    {
        $codigo = $this->getCodigoTipo();
        
        // Cache del tipo de notificación por 1 hora
        $cacheKey = "tipo_notificacion_{$codigo}";
        
        $this->tipoNotificacion = Cache::remember($cacheKey, 3600, function () use ($codigo) {
            return TipoNotificacion::where('codigo', $codigo)
                ->where('estatus', true)
                ->first();
        });

        if (!$this->tipoNotificacion) {
            Log::warning("Tipo de notificación no encontrado o inactivo: {$codigo}");
        }
    }

    /**
     * Determinar los canales de envío de la notificación
     */
    public function via($notifiable): array
    {
        if (!$this->tipoNotificacion) {
            // Si no hay tipo definido, solo usar database como fallback
            return ['database'];
        }

        return $this->tipoNotificacion->canales_to_use;
    }

    /**
     * Obtener la representación para el canal database
     */
    public function toDatabase($notifiable): array
    {
        $data = [
            'tipo_notificacion_id' => $this->tipoNotificacion?->id,
            'titulo' => $this->getTitulo(),
            'mensaje' => $this->getMensaje(),
            'icono' => $this->tipoNotificacion?->icono ?? 'notifications-outline',
            'color' => $this->tipoNotificacion?->color ?? 'primary',
            'url' => $this->generarUrl(),
            'entity_id' => $this->entityId,
            'data' => $this->notificationData,
        ];

        return $data;
    }

    /**
     * Obtener la representación para el canal mail
     */
    public function toMail($notifiable): MailMessage
    {
        $mailMessage = (new MailMessage)
            ->subject($this->getTitulo())
            ->line($this->getMensaje());

        $url = $this->generarUrl();
        if ($url) {
            $mailMessage->action('Ver detalles', $url);
        }

        // Permitir que las notificaciones concretas personalicen el mail
        return $this->customizeMailMessage($mailMessage, $notifiable);
    }

    /**
     * Obtener la representación para el canal broadcast
     */
    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'tipo_notificacion_id' => $this->tipoNotificacion?->id,
            'titulo' => $this->getTitulo(),
            'mensaje' => $this->getMensaje(),
            'icono' => $this->tipoNotificacion?->icono ?? 'notifications-outline',
            'color' => $this->tipoNotificacion?->color ?? 'primary',
            'url' => $this->generarUrl(),
            'entity_id' => $this->entityId,
            'data' => $this->notificationData,
            'created_at' => now()->toISOString(),
        ]);
    }

    /**
     * Generar la URL de la notificación
     */
    protected function generarUrl(): ?string
    {
        // Si hay URL personalizada, usarla
        if ($this->customUrl) {
            return $this->customUrl;
        }

        // Si no hay tipo de notificación, no hay URL
        if (!$this->tipoNotificacion) {
            return null;
        }

        // Usar la URL del tipo de notificación
        return $this->tipoNotificacion->generarUrl($this->entityId);
    }

    /**
     * Método que pueden sobrescribir las notificaciones concretas
     * para personalizar el mensaje de email
     */
    protected function customizeMailMessage(MailMessage $mailMessage, $notifiable): MailMessage
    {
        return $mailMessage;
    }

    /**
     * Obtener datos adicionales específicos de la notificación concreta
     */
    protected function getAdditionalData(): array
    {
        return [];
    }

    /**
     * Método para obtener toda la data que será almacenada
     */
    public function getNotificationData(): array
    {
        return array_merge($this->notificationData, $this->getAdditionalData());
    }

    /**
     * Obtener el tipo de notificación
     */
    public function getTipoNotificacion(): ?TipoNotificacion
    {
        return $this->tipoNotificacion;
    }

    /**
     * Determinar el canal de broadcast
     */
    public function broadcastOn(): array
    {
        return [];
    }

    /**
     * Personalizar el tipo de broadcast
     */
    public function broadcastAs(): string
    {
        return 'notification.new';
    }

    /**
     * Configurar si la notificación debe ser encolada
     */
    public function shouldQueue(): bool
    {
        // No encolar durante las pruebas o cuando la cola esté configurada como 'sync'
        return config('queue.default') !== 'sync' && !app()->runningInConsole();
    }

    /**
     * Manejar fallo en el envío de la notificación
     */
    public function failed(\Exception $exception): void
    {
        Log::error('Fallo al enviar notificación', [
            'notification_type' => static::class,
            'tipo_codigo' => $this->getCodigoTipo(),
            'error' => $exception->getMessage(),
            'data' => $this->notificationData,
        ]);
    }
}
