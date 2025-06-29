<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SucursalResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'direccion' => $this->direccion,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'encargado' => $this->encargado,
            'activa' => $this->activa,
            'coordenadas_lat' => $this->coordenadas_lat,
            'coordenadas_lng' => $this->coordenadas_lng,
            'productos_count' => $this->whenCounted('productos'),
            'productos' => ProductoResource::collection($this->whenLoaded('productos')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
