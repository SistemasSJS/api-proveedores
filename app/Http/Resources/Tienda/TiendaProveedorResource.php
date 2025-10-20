<?php

namespace App\Http\Resources\Tienda;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TiendaProveedorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'nombre' => $this->nombre,
            'logo' => asset('storage/'.$this->logo),
            'principal' => (bool) $this->principal,
            'activo' => (bool) $this->activo,
            'calificacion' => (float) $this->calificacion,
            'totalProductos' => (int) $this->total_productos,
            'tiempoEntrega' => $this->tiempo_entrega,
        ];
    }
}
