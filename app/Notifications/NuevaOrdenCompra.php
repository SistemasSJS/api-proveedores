<?php

namespace App\Notifications;

use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Notifications\Notification;

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

    public function via(object $notifiable): array
    {
        return ['broadcast', 'database'];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
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
        ]);
    }

    public function broadcastType(): string
    {
        return 'nueva-orden-compra';
    }

    public function broadcastOn()
    {
        return [new Channel('public-notifications')];
    }

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
}
