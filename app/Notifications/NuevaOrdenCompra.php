<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Notification;

/**
 * Notificación enviada cuando se crea una nueva orden de compra
 */
class NuevaOrdenCompra extends Notification implements ShouldBroadcast
{
    use Queueable;

    public $ordenCompraId;
    public $proveedorId;
    public $empresaId;
    public $estatus;

    public function __construct(
        string $ordenCompraId,
        int $proveedorId,
        int $empresaId,
        ?string $estatus = null
    ) {
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
        return ['broadcast', 'database'];
    }

    /**
     * Representación para broadcast (WebSocket)
     */
    public function toBroadcast(object $notifiable): array
    {
        return [
            'id' => $this->id,
            'tipo' => 'nueva_orden_compra',
            'titulo' => 'Nueva Orden de Compra #' . $this->ordenCompraId,
            'mensaje' => "Tienes una nueva orden de compra: {$this->ordenCompraId}",
            'data' => [
                'orden_compra_id' => $this->ordenCompraId,
                'proveedor_id' => $this->proveedorId,
                'empresa_id' => $this->empresaId,
                'estatus' => $this->estatus,
            ],
            'timestamp' => now()->toIsoString(),
            'read_at' => null,
        ];
    }

    /**
     * Tipo de evento broadcast
     */
    public function broadcastType(): string
    {
        return 'nueva-orden-compra';
    }

    /**
     * Representación para base de datos
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
            'timestamp' => now()->toIsoString(),
        ];
    }

    /**
     * Configurar colas por canal
     */
    public function viaQueues(): array
    {
        return [
            'broadcast' => 'notifications',
            'database' => 'default',
        ];
    }
}
