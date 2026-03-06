<?php

namespace App\Http\Resources\Presupuesto;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProveedorPresupuestoCarteraClienteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'proveedor_id' => $this->proveedor_id,
            'nombre' => $this->nombre,
            'puesto' => $this->puesto,
            'empresa' => $this->empresa,
            'telefono' => $this->telefono,
            'correo' => $this->correo,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
