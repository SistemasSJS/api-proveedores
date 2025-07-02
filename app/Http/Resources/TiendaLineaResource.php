<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TiendaLineaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => (string) $this->id,
            'nombre'          => $this->nombre,
            'marca'           => new TiendaMarcaResource($this->whenLoaded('marca')),
            'activa'          => (bool) $this->activa,
            'totalProductos'  => (int) $this->total_productos,
        ];
    }
}
