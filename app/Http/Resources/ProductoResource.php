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
            'nombre'         => $this->nombre,
            'logo' => $this->logo
                ? (preg_match('/^https?:\/\//', $this->logo) ? $this->logo : asset('storage/' . $this->logo))
                : null,
            'descripcion'    => $this->descripcion,
            'sku'            => $this->sku,
            'marca_id'       => $this->marca_id,
            'linea_id'       => $this->linea_id,
            'proveedor_id'    => $this->proveedor_id,
            'unidad_medida_id' => $this->unidad_medida_id,

            // Relaciones
            'marca' => $this->whenLoaded('marca', function () {
                return [
                    'id'     => $this->marca->id,
                    'nombre' => $this->marca->nombre,
                ];
            }),

            'linea' => $this->whenLoaded('linea', function () {
                return [
                    'id'     => $this->linea->id,
                    'nombre' => $this->linea->nombre,
                ];
            }),

            'catalogo' => $this->whenLoaded('catalogo', function () {
                return [
                    'id'     => $this->catalogo->id,
                    'nombre' => $this->catalogo->nombre,
                ];
            }),

            'unidad_medida' => $this->whenLoaded('unidad_medida', function () {
                return [
                    'id'     => $this->unidad_medida->id,
                    'nombre' => $this->unidad_medida->nombre,
                    'clave'  => $this->unidad_medida->clave,
                ];
            }),

            'imagenes' => $this->whenLoaded('imagenes', function () {
                return $this->imagenes->map(function ($imagen) {
                    return [
                        'id'  => $imagen->id,
                        'url' => $imagen->url,
                        'tipo' => $imagen->tipo,
                    ];
                });
            }),
            'categorias' => $this->whenLoaded('categorias', function () {
                return $this->categorias->map(function ($categoria) {
                    return [
                        'id'                 => $categoria->id,
                        'nombre'             => $categoria->nombre,
                        'categoria_padre'    => $categoria->whenLoaded('categoria_padre', function () use ($categoria) {
                            return [
                                'id'     => $categoria->categoria_padre->id,
                                'nombre' => $categoria->categoria_padre->nombre,
                            ];
                        }),
                    ];
                });
            }),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
