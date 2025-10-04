<?php

namespace App\Http\Resources\Proveedor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProveedorEmpresaConstruccResource extends JsonResource
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
            'nombre' => $this->nombre,
            'rfc' => $this->rfc,
            'razon_social' => $this->razon_social,
            'direccion' => $this->direccion,
            'ciudad' => $this->ciudad,
            'estado' => $this->estado,
            'codigo_postal' => $this->codigo_postal,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'representante_legal' => $this->representante_legal,
            'proveedor_id' => $this->proveedor_id,
            'activo' => (bool) $this->activo,
            
            // Información combinada para mostrar
            'nombre_completo' => $this->nombre_completo,
            'direccion_completa' => $this->direccion . ', ' . $this->ciudad . ', ' . $this->estado . ' ' . $this->codigo_postal,
            
            // Relaciones
            'proveedor' => $this->whenLoaded('proveedor', function () {
                return [
                    'id' => $this->proveedor->id,
                    'nombre' => $this->proveedor->nombre_comercial ?? $this->proveedor->nombre,
                ];
            }),
            
            // Metadatos
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}