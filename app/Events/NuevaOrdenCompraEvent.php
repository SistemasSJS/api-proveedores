<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NuevaOrdenCompraEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $notificacionId;
    public $empresaId;
    public $proveedorId;
    public $ordenCompraId;
    public  $estatus;
    public  $userId;

    public function __construct($data)
    {
        $this->notificacionId = $data['notificacion_id'];
        $this->empresaId = $data['empresa_id'];
        $this->proveedorId = $data['proveedor_id'];
        $this->ordenCompraId = $data['orden_compra_id'];
        $this->estatus = $data['estatus'] ?? null;
        $this->userId = $data['user_id'] ?? null;
    }

    public function broadcastOn()
    {
        $channels = [
            new Channel('public-notifications'),  // Canal público
        ];

        // Agregar canal de usuario si existe
        if ($this->userId) {
            $channels[] = new PrivateChannel('App.Models.User.' . $this->userId);
        }

        return $channels;
    }

    public function broadcastAs()
    {
        return 'NuevaOrdenCompra';
    }

    public function broadcastWith()
    {
        return [
            'notificacion_id' => $this->notificacionId,
            'empresa_id' => $this->empresaId,
            'proveedor_id' => $this->proveedorId,
            'orden_compra_id' => $this->ordenCompraId,
            'estatus' => $this->estatus,
            'timestamp' => now()->toIso8601String()
        ];
    }
}
