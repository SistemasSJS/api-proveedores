<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NuevaOrdenCompraEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $notificacionId;
    public $proveedorId;
    public $numOrden;
    public $fecha;
    public $obraId;
    public $empresa;
    public $usuario;
    public $tipoOrden;
    public $requisicionId;
    public $tieneRequisicion;
    public $subtotal;
    public $iva;
    public $tasa;
    public $importe;
    public $estatus;
    public $observaciones;
    public $titulo;
    public $mensaje;

    public function __construct($data)
    {
        $this->notificacionId = $data['notificacion_id'];
        $this->proveedorId = $data['proveedor_id'];
        $this->numOrden = $data['num_orden'];
        $this->fecha = $data['fecha'];
        $this->obraId = $data['obra_id'];
        $this->empresa = $data['empresa'];
        $this->usuario = $data['usuario'] ?? null;
        $this->tipoOrden = $data['tipo_orden'];
        $this->requisicionId = $data['requisicion_id'] ?? null;
        $this->tieneRequisicion = $data['tiene_requisicion'];
        $this->subtotal = $data['subtotal'];
        $this->iva = $data['iva'];
        $this->tasa = $data['tasa'];
        $this->importe = $data['importe'];
        $this->estatus = $data['estatus'];
        $this->observaciones = $data['observaciones'] ?? null;
        $this->titulo = $data['titulo'];
        $this->mensaje = $data['mensaje'];
    }

    public function broadcastOn()
    {
        return new Channel('proveedor.' . $this->proveedorId);
    }

    public function broadcastAs()
    {
        return 'NuevaOrdenCompra';
    }

    public function broadcastWith()
    {
        return [
            'notificacion_id' => $this->notificacionId,
            'num_orden' => $this->numOrden,
            'fecha' => $this->fecha,
            'obra_id' => $this->obraId,
            'empresa' => $this->empresa,
            'usuario' => $this->usuario,
            'tipo_orden' => $this->tipoOrden,
            'requisicion_id' => $this->requisicionId,
            'tiene_requisicion' => $this->tieneRequisicion,
            'subtotal' => $this->subtotal,
            'iva' => $this->iva,
            'tasa' => $this->tasa,
            'importe' => $this->importe,
            'estatus' => $this->estatus,
            'observaciones' => $this->observaciones,
            'titulo' => $this->titulo,
            'mensaje' => $this->mensaje,
            'timestamp' => now()->toIso8601String()
        ];
    }
}
