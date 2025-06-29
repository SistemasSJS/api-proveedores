<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificacionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'titulo' => $this->titulo,
            'mensaje' => $this->mensaje,
            'leida' => $this->leida,
            'fecha_lectura' => $this->fecha_lectura,
            'datos' => $this->datos,
            'created_at' => $this->created_at,
        ];
    }
}
