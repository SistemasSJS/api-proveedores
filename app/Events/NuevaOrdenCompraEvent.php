<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NuevaOrdenCompraEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $empresaId;
    public $proveedorId;
    public $ordenCompraId;
    public $estatus;

    public function __construct($data)
    {
        $this->empresaId = $data['empresa_id'];
        $this->proveedorId = $data['proveedor_id'];
        $this->ordenCompraId = $data['orden_compra_id'];
        $this->estatus = $data['estatus'] ?? null;
    }

    public function broadcastOn()
    {
        // Usar los tres canales definidos
        return [
            new Channel('public-notifications'),                    // Canal público
            new PrivateChannel('proveedor.' . $this->proveedorId), // Canal privado por proveedor
            new PrivateChannel('App.Models.User.' . $this->proveedorId), // Canal privado por usuario
        ];
    }

    public function broadcastAs()
    {
        return 'NuevaOrdenCompra';
    }

    public function broadcastWith()
    {
        return [
            'empresa_id' => $this->empresaId,
            'proveedor_id' => $this->proveedorId,
            'orden_compra_id' => $this->ordenCompraId,
            'estatus' => $this->estatus,
            'timestamp' => now()->toIso8601String()
        ];
    }
}
