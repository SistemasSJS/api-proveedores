<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TiendaProductoResource extends JsonResource
{
  public function toArray($request)
  {
    return [
      'id'               => (string) $this->id,
      'nombre'           => $this->nombre_comercial,
      'descripcion'      => $this->descripcion,
      'precio'           => (float) $this->precio,
      'precioAnterior'   => $this->whenNotNull($this->precio_anterior),
      'imagen_principal' => $this->imagen_principal ? asset('storage/' . $this->imagen_principal) : '',
      'imagenes'         => $this->imagenes ?? [],
      'marca'            => new TiendaMarcaResource($this->whenLoaded('marca')),
      'linea'            => new TiendaLineaResource($this->whenLoaded('linea')),
      'proveedor'        => new TiendaProveedorResource($this->whenLoaded('proveedor')),
      'categoria'        => new TiendaCategoriaResource($this->whenLoaded('categoria')),
      'stock'            => (int) $this->stock,
      'especificaciones' => $this->especificaciones ?? [],
      'puntuacion'       => (float) $this->puntuacion,
      'totalPedidos'     => (int) $this->total_pedidos,
      'fechaCreacion'    => $this->created_at ? $this->created_at->toISOString() : null,
      'activo'           => (bool) $this->activo,
      'destacado'        => (bool) $this->destacado,
      'enOferta'         => (bool) $this->en_oferta,
      'tags'             => $this->tags ?? [],
    ];
  }
}
