<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProveedorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'nombre'     => $this->nombre,
            'rfc'        => $this->rfc,
            'telefono'   => $this->telefono,
            'email'      => $this->email,
            'direccion'  => $this->direccion,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            // Agrega aquí más campos o relaciones si lo necesitas
        ];
    }
}
