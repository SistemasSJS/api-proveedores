<?php

namespace App\Http\Resources;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProveedorProductoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
                    'id' => $this->id,
            'sku' => $this->sku,
            'codigo_interno' => $this->codigo_interno,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'modelo' => $this->modelo,

            // Precios
            'precio_base' => (float) $this->precio_base,
            'precio_mayoreo' => (float) $this->precio_mayoreo,
            'precio_menudeo' => (float) $this->precio_menudeo,

            // Imagen principal optimizada
            'imagen_principal' => $this->imagen_principal
                ? (preg_match('/^https?:\/\//', $this->imagen_principal)
                    ? $this->imagen_principal
                    : asset('storage/' . $this->imagen_principal))
                : null,
            'marca_id'       => $this->marca_id,
            'categoria_id' => $this->categoria_id,
            'subcategoria_id' => $this->subcategoria_id,
            'proveedor_id'    => $this->proveedor_id,
            'unidad_medida_id' => $this->unidad_medida_id,
    
            // Categorización del producto
            'categoria' => $this->when($this->relationLoaded('categoria'), function () {
                return $this->categoria ? [
                    'id' => $this->categoria->id,
                    'nombre' => $this->categoria->nombre,
                    'descripcion' => $this->categoria->descripcion,
                ] : null;
            }),

            'subcategoria' => $this->when($this->relationLoaded('subcategoria'), function () {
                return $this->subcategoria ? [
                    'id' => $this->subcategoria->id,
                    'nombre' => $this->subcategoria->nombre,
                    'descripcion' => $this->subcategoria->descripcion,
                ] : null;
            }),

            'marca' => $this->when($this->relationLoaded('marca'), function () {
                return $this->marca ? [
                    'id' => $this->marca->id,
                    'nombre' => $this->marca->nombre,
                    'descripcion' => $this->marca->descripcion,
                    'logo' => $this->marca->logo
                        ? (preg_match('/^https?:\/\//', $this->marca->logo)
                            ? $this->marca->logo
                            : asset('storage/' . $this->marca->logo))
                        : null,
                ] : null;
            }),

            'unidad_medida' => $this->when($this->relationLoaded('unidad_medida'), function () {
                return $this->unidad_medida ? [
                    'id' => $this->unidad_medida->id,
                    'nombre' => $this->unidad_medida->nombre,
                    'clave' => $this->unidad_medida->clave,
                    'descripcion' => $this->unidad_medida->descripcion,
                ] : null;
            }),

            // Metadatos
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
