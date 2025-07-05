<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'sku'            => $this->sku,
            'nombre'         => $this->nombre,
            'descripcion'    => $this->descripcion,
            // 'imagen_principal' => $this->imagen_principal
            //     ? (preg_match('/^https?:\/\//', $this->imagen_principal) ? $this->imagen_principal : asset('storage/' . $this->imagen_principal))
            //     : null,
            'imagen_principal' => $this->imagen_principal
                ? asset('storage/' . $this->imagen_principal)
                : null,
            //
            'marca_id'       => $this->marca_id,
            'linea_id'       => $this->linea_id,
            'categoria_id' => $this->unidad_medida_id,
            'proveedor_id'    => $this->proveedor_id,
            'unidad_medida_id' => $this->unidad_medida_id,

            // Relaciones
            'marca' => new  MarcaResource($this->whenLoaded('marca')),
            'linea' => new LineaResource($this->whenLoaded('linea')),
            'categoria' => new LineaResource($this->whenLoaded('categoria')),
            'especificaciones' => EspecificacionesResource::collection($this->whenLoaded('especificaciones')),
            'unidad_medida' => new UnidadMedidaResource($this->whenLoaded('unidad_medida')),
            'imagenes' => [],
            // 'imagenes' => Imagen($this->whenLoaded('imagenes')),
            // el producto solo debe tener uyna categoria
            // 'categorias' => CategoriaResource::collection($this->whenLoaded('categorias')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
