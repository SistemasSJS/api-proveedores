<?php

namespace App\Http\Resources\Tienda;

use App\Support\PublicStorageUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class TiendaProveedorResource extends JsonResource
{
    /**
     * Convierte un string a mayúsculas (UTF-8). Null o vacío se devuelven tal cual.
     */
    private static function upper(?string $value): ?string
    {
        return $value !== null && $value !== '' ? Str::upper($value) : $value;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'nombre' => self::upper($this->nombre),
            'logo' => PublicStorageUrl::make($this->logo),
            'principal' => (bool) $this->principal,
            'activo' => (bool) $this->activo,
            'calificacion' => (float) $this->calificacion,
            'totalProductos' => (int) $this->total_productos,
            'tiempoEntrega' => $this->tiempo_entrega,
        ];
    }
}
