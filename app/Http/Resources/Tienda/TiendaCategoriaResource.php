<?php

namespace App\Http\Resources\Tienda;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TiendaCategoriaResource extends JsonResource
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
            'icono'           => $this->icono,
            'activa'          => (bool) $this->activa,
            'totalProductos'  => (int) $this->total_productos,
        ];
    }
}
