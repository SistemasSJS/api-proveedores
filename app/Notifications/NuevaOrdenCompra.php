<?php

namespace App\Notifications;

use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Notifications\Notification;
use App\Services\FcmService;

class NuevaOrdenCompra extends Notification implements ShouldBroadcastNow
{
    public $ordenCompraId;
    public $proveedorId;
    public $empresaId;
    public $estatus;

    public function __construct(string $ordenCompraId, int $proveedorId, int $empresaId, ?string $estatus = null)
    {
        $this->ordenCompraId = $ordenCompraId;
        $this->proveedorId = $proveedorId;
        $this->empresaId = $empresaId;
        $this->estatus = $estatus ?? 'pendiente';
    }

    /**
     * Canales de notificación
     */
    public function via(object $notifiable): array
    {
        $via = ['broadcast', 'database', 'mail'];

        // if (method_exists($notifiable, 'deviceTokens') && $notifiable->deviceTokens()->where('is_active', true)->exists()) {
        //     $via[] = 'fcm';
        // }

        return $via;
    }

    /**
     * Canal Broadcast (WebSocket) — PRIVADO POR USUARIO
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'tipo' => 'nueva_orden_compra',
            'titulo' => 'Nueva Orden de Compra #' . $this->ordenCompraId,
            'mensaje' => "Tienes una nueva orden de compra: {$this->ordenCompraId}",
            'data' => [
                'orden_compra_id' => $this->ordenCompraId,
                'proveedor_id' => $this->proveedorId,
                'empresa_id' => $this->empresaId,
                'estatus' => $this->estatus,
            ],
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function broadcastType(): string
    {
        return 'nueva-orden-compra';
    }

    /**
     * Canal privado por usuario
     */
    // public function broadcastOn(): array
    // {
    //     return [new PrivateChannel('App.Models.User.' . $this->notifiable->id)];
    // }

    /**
     * Canal Database
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'nueva_orden_compra',
            'titulo' => 'Nueva Orden de Compra #' . $this->ordenCompraId,
            'mensaje' => "Tienes una nueva orden de compra: {$this->ordenCompraId}",
            'orden_compra_id' => $this->ordenCompraId,
            'proveedor_id' => $this->proveedorId,
            'empresa_id' => $this->empresaId,
            'estatus' => $this->estatus,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Canal Mail
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nueva Orden de Compra #' . $this->ordenCompraId)
            ->greeting('Hola ' . $notifiable->name . ',')
            ->line('Tienes una nueva orden de compra registrada.')
            ->line('Número de orden: ' . $this->ordenCompraId)
            ->line('Estatus: ' . ucfirst($this->estatus))
            ->action('Ver Orden', url('/ordenes-compra/' . $this->ordenCompraId))
            ->line('Gracias por utilizar CONSTRUCC.');
    }

    /**
     * Canal FCM personalizado
     */
    public function toFcm(object $notifiable): void
    {
        $tokens = $notifiable->deviceTokens()
            ->where('is_active', true)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return;
        }

        $notification = [
            'title' => '📦 Nueva Orden de Compra #' . $this->ordenCompraId,
            'body' => "Tienes una nueva orden de compra disponible.",
        ];

        $data = [
            'tipo' => 'nueva_orden_compra',
            'orden_compra_id' => $this->ordenCompraId,
            'proveedor_id' => $this->proveedorId,
            'empresa_id' => $this->empresaId,
            'estatus' => $this->estatus,
            'timestamp' => now()->toIso8601String(),
        ];

        app(FcmService::class)->sendToTokens($tokens, $notification, $data);
    }
}
