<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\CategoriaAcordeonResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminProveedorAcordeonResource extends JsonResource
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
            'nombre' => $this->nombre_comercial ?? $this->razon_social,
            'count' => $this->productos_count ?? 0,
            'childs' => CategoriaAcordeonResource::collection(
                $this->whenLoaded('categorias')
            ),
        ];
    }
}
