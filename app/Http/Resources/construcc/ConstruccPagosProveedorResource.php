<?php

namespace App\Http\Resources\Construcc;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ConstruccPagosProveedorResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @param  Request  $request
   * @return array
   */
  public function toArray($request): array
  {
    return [

      /* =========================
             * Identificación
             * ========================= */
      'id' => $this->id,
      'nombre' => $this->nombre_comercial,
      'razon_social' => $this->razon_social,
      'nombre_comercial' => $this->nombre_comercial,
      'rfc' => $this->rfc,

      /* =========================
             * Contacto general
             * ========================= */
      'telefono' => $this->telefono,
      'email' => $this->email,
      'pagina_web' => $this->pagina_web,

      /* =========================
             * Archivos
             * ========================= */
      'logo' => $this->logo
        ? Storage::disk('public')->url($this->logo)
        : null,

      // Contadores
      'spp_autorizadas_count' => (int) ($this->spp_autorizadas_count ?? 0),
    ];
  }
}
