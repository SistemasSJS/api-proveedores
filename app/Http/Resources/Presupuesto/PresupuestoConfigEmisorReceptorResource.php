<?php

namespace App\Http\Resources\Presupuesto;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PresupuestoConfigEmisorReceptorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'proveedor_id' => $this->proveedor_id,
            'tipo' => $this->tipo == 1 ? 'emisor' : 'receptor',
            'nombre_completo' => $this->nombre . ' ' . $this->apellido,
            'puesto' => $this->puesto,
            'file_firma' => $this->file_firma ? url('storage/' . $this->file_firma) : null,
            'estado' => $this->estado == 1 ? 'Activo' : ($this->estado == 2 ? 'Inactivo' : 'Default'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
