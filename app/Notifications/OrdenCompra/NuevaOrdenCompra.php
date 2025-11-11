<?php

namespace App\Notifications\OrdenCompra;

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

        if (method_exists($notifiable, 'deviceTokens') && $notifiable->deviceTokens()->where('is_active', true)->exists()) {
            $via[] = 'fcm';
        }

        return $via;
    }

    /**
     * Canal Broadcast (WebSocket) — PRIVADO POR USUARIO
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'tipo' => 'orden_compra',  // Categoría base
            'subtipo' => 'nueva',       // Tipo específico
            'titulo' => 'Nueva Orden de Compra #' . $this->ordenCompraId,
            'mensaje' => "Tienes una nueva orden de compra: {$this->ordenCompraId}",
            'action_url' => '/ordenes-compra/' . $this->ordenCompraId,
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
        return 'orden-compra';
    }

    /**
     * Canal privado por usuario
     */
    public function broadcastOn(): array
    {
        // No necesitas especificar el canal aquí cuando usas Notification
        // Laravel automáticamente envía al canal privado del notifiable (usuario)
        // Esto enviará a: private-App.Models.User.{userId}
        return [];
    }

    /**
     * Canal Database
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'orden_compra',  // Categoría base
            'subtipo' => 'nueva',       // Tipo específico
            'titulo' => 'Nueva Orden de Compra #' . $this->ordenCompraId,
            'mensaje' => "Tienes una nueva orden de compra: {$this->ordenCompraId}",
            'action_url' => '/ordenes-compra/' . $this->ordenCompraId,
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
        $frontendUrl = config('app.frontend_url', config('app.url'));
        $urlOrden = $frontendUrl . '/ordenes-compra/' . $this->ordenCompraId;

        return (new MailMessage)
            ->subject('Nueva Orden de Compra #' . $this->ordenCompraId)
            ->view('emails.orden-compra.nueva', [
                'notifiable' => $notifiable,
                'ordenCompraId' => $this->ordenCompraId,
                'proveedorId' => $this->proveedorId,
                'empresaId' => $this->empresaId,
                'estatus' => $this->estatus,
                'urlOrden' => $urlOrden,
            ]);
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
            'tipo' => 'orden_compra',  // Categoría base
            'subtipo' => 'nueva',       // Tipo específico
            'action_url' => '/ordenes-compra/' . $this->ordenCompraId,
            'orden_compra_id' => $this->ordenCompraId,
            'proveedor_id' => (string) $this->proveedorId,
            'empresa_id' => (string) $this->empresaId,
            'estatus' => $this->estatus,
            'timestamp' => now()->toIso8601String(),
        ];

        app(FcmService::class)->sendToTokens($tokens, $notification, $data);
    }
}
