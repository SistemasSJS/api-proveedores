<?php

namespace App\Http\Resources\Presupuesto;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PresupuestoAnexoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'presupuesto_id' => (int) $this->presupuesto_id,
            'titulo' => $this->titulo,
            'descripcion' => $this->descripcion,
            'precio' => $this->precio !== null ? (float) $this->precio : null,
            'orden' => (int) $this->orden,
            'archivo_path' => $this->archivo_path,
            'archivo_url' => $this->archivo_path
                ? Storage::disk('public')->url($this->archivo_path)
                : null,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
