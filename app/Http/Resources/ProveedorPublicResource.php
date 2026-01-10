<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProveedorPublicResource extends JsonResource
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
            'logo' => $this->logo
                ? Storage::disk('public')->url($this->logo)
                : null,
            'nombre_comercial' => $this->nombre_comercial,
            'email' => $this->email,
            'telefono' => $this->telefono,
            'direccion_empresa' => $this->direccion_empresa,
            'constancia_fiscal' => $this->constancia_fiscal
                ? Storage::disk('public')->url($this->constancia_fiscal)
                : null,
        ];
    }
}
