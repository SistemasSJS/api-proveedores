<?php

namespace App\Http\Resources\Presupuesto;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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
            'estado' => $this->estado == 1 ? 'activo' : ($this->estado == 2 ? 'inactivo' : 'default'),
            'informacion_general' => [
                'foto_perfil' => $this->foto_perfil ? Storage::disk('public')->url($this->foto_perfil) : null,
                'subfijo' => $this->subfijo,
                'nombre' => $this->nombre,
                'ape1' => $this->ape1,
                'ape2' => $this->ape2,
                'puesto' => $this->puesto,
                'file_firma' => $this->file_firma ? Storage::disk('public')->url($this->file_firma) : null,
            ],
            'info_contacto' => [
                'telefono' => $this->telefono,
                'correo' => $this->correo,
            ],
            'config_tarjeta' => [
                'color_fondo' => $this->color_fondo,
            ],
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
